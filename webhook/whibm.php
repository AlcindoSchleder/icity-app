<?php

/**
 * Programa para buscar os resultados da análise das redes sociais
 *
 * @version    1.0.0
 * @package    VocatioTelecom
 * @subpackage login
 * @author     Alcindo Schleder <alcindoschleder@gmail.com>
 * @copyright (c) date('Y'), Alcindo Schleder
 */

include_once '../../include/config.inc.php';

class IBMWebHook extends Base {

    /**
     * Nome da variável de ambiente das credenciais
     */
    const CREDENTIALS = 'VCAP_SERVICES';
    //userid:14c681c7d4813b4ddc6228566bd9daac1cb61aa1967c56c661ed84df88825677 
    //pwd:xIct7041f49476eb778b
    const VOCATIO_PROFILE_URL = 'https://www.vocatiotelecom.com.br/login/connections/';
    const CUSTOM_ACTIONS      = array('send-profile', 'initialization', 'message', 'complete');

    private $Credentials      = false;
    private $action           = false;
    private $code             = false;
    private $message          = false;
    private $appType          = false;
    private $userID           = false;
    private $userEmail        = false;
    private $userName         = false;
    private $token            = false;
    private $hashID           = false;
    private $profile          = false;
    private $pkVisitors       = false;

    public function __construct(array $data = null) {
        parent::__construct();
        $this->action     = ((isset($data['action']))      ? $data['action']      : false);
        $this->code       = ((isset($data['code']))        ? $data['code']        : false);
        $this->message    = ((isset($data['message']))     ? $data['message']     : false);
        $this->appType    = ((isset($data['appTP']))       ? $data['appTP']       : false);
        $this->pkVisitors = ((isset($data['pkUser']))      ? $data['pkUser']      : false);
        $this->userID     = ((isset($data['userID']))      ? $data['userID']      : false);
        $this->userEmail  = ((isset($data['email_user']))  ? $data['email_user']  : false);
        $this->userName   = ((isset($data['name_user']))   ? $data['name_user']   : false);
        $this->token      = ((isset($data['token']))       ? $data['token']       : false);
        $this->hashID     = ((isset($data['hashID']))      ? $data['hashID']      : false);
        $this->profile    = ((isset($data['profile']))     ? $data['profile']     : false);
        $this->getCredentials();
    }
    public function __destruct() {
        $this->Credentials = null;
        parent::__destruct();
    }
    /**
     * Busca as credenciais da IBM para acessar a API
     */
    private function getCredentials() {
        $this->Credentials = $this->getJsonEnvironmentVAR(self::CREDENTIALS);
    }
    /**
     * Função que retorna o token do usuário para executar na plataforma 
     * se não for encontrado retorna uma string 'none'
     * @return string
     */
    private function getUserID() {
        return ((isset($this->Credentials['ibmWebHook']['credentials']['userid'])) ? $this->Credentials['ibmWebHook']['credentials']['userid'] : 'none');
    }
    /**
     * Função que retorna a senha do token do usuário para executar na plataforma 
     * se não for encontrado retorna uma string 'none'
     * @return string
     */
    private function getUserPwd() {
        return ((isset($this->Credentials['ibmWebHook']['credentials']['password'])) ? $this->Credentials['ibmWebHook']['credentials']['password'] : 'none');
    }
    /**
     * Retorna o cáclulo do hash que forma o token de acesso de cada usuário
     * 
     * @return string
     */
    private function calcToken() {
        $token = hash_hmac('sha256', $this->pkVisitors . $this->getUserID(), $this->getUserPwd());
        $pwd = hash_hmac('ripemd128', $this->pkVisitors . $this->getUserPwd(), VOCATIO_KEY);
        return hash_hmac('sha256', $token, $pwd);
    }
    private function sendResponse() {
        echo(json_encode($this->result));
    }
    private function prepareResponse(string $action = null, string $requestID = null) {
        self::setLogData(null, "[WebHookIBM]\n    Enviando resposta para {$action}!");
        $this->result['response'] = array(
            'data' => array(
                'action' => (($action) ? ($action) : 'message'),
                'code' => $this->result['code'],
                'message' => $this->result['message'],
                'token' => $this->calcToken(),
                'requestID' => (($requestID) ? $requestID : 'none')
            )
        );
        $this->sendResponse();
    }
    /**
     * Função que processa a a mensagem do sistema
     */
    private function processProfile() {
        if ($this->profile):
            self::setLogData(null, "[WebHookIBM]\n    Profile to {$this->userName} / {$this->pkVisitors} user has been successfuly created!");
            self::setMessage(STT_OK, "Perfil do usuário {$this->userName} / {$this->pkVisitors} criado com sucesso!");
            $this->saveProfileIntoDB();
        else:
            self::setLogData(null, "[WebHookIBM]\n    Profile to {$this->userName} / {$this->pkVisitors} wasn't receiveid!");
            self::setMessage(STT_ERROR, "Perfil do usuário {$this->userName} / {$this->pkVisitors} não recebido!");
        endif;
        $this->prepareResponse('response');
    }
    /**
     * Função que salva os dados do perfil nos Bancos de dados ativos
     */
    private function saveProfileIntoDB() {
        $data = array (
            'profile' => $this->profile,
            'pkVisitors' => $this->pkVisitors,
            'pkVisitorsLogins' => $this->appType
        );
        $saveProfile = new SaveProfile($data);
        $saveProfile->saveProfileIntoDB($saveProfile::VOCATIO_PERSONALITY);
        $saveProfile = null;
    }
    /**
     * Função que verifica se o token recebido pela snam é igual ao das credenciais 
     * @param string $pk Token de verificação da Vocatio Telecom recebido pela snam
     * @return boolean
     */
    public function verifyToken() {
        $res = false;
        if ($this->token):
            $res = ($this->token == $this->calcToken());
        endif;
        if (!$res):
            self::setLogData(null, "[WebHookIBM]\nUsuário náo Autorizado:\n    token:{$this->token}!");
        endif;
        return $res;
    }
    /**
     * Função que gera o processamento de cada tipo de mensagem enviada
     */
    public function processMessage() {
        self::setLogData(null, "[WebHookIBM]\nMensagem Recebida com action: {$this->action}!");
        switch ($this->action):
            case 'complete':
                $this->prepareResponse('send-profile', $this->hashID);
                break;
            case 'send-profile':
                $this->processProfile();
                $this->prepareResponse('message');
                break;
            default:
                self::setMessage(STT_ERROR, 'Comando Inválido!');
                $this->prepareResponse('error');
                break;
        endswitch;
    }
}

// init program
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data)):
    $action = ((isset($data['data']['action'])) ? $data['data']['action'] : false);
    if (($action) && (in_array($action, IBMWebHook::CUSTOM_ACTIONS))):
        Base::setLogData(null, "[WebHookIBM]\nIniciando a Coleta no WebHook");
        $hook = new IBMWebHook($data['data']);
        if ($hook->verifyToken()):
            $hook->processMessage();
        endif;
        $hook = null;
    endif;
else:
    Base::setLogData(null, "[WebHookIBM]\nDados do array não foram enviados");
    $response = Base::setMessage(STT_ERROR, 'IBM Pesonality Insights: Dados do array não foram enviados');
endif;
