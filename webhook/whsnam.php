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

class SnamWebHook extends Base {
    
    const SNAM_ENV     = 'SNAM_APP_DATA';
    const SNAM_URL     = 'https://{#app#}.snam.io/v2.1/{#action#}/{#requisition_sid#}/connections';
    const SNAM_REQ_URL = 'https://{#app#}.snam.io/v2.1/{#action#}/{#requisition_sid#}';

    private $requisitionID    = false;
    private $Credentials      = false;
    private $pkTypeProfile    = false;
    private $pkVisitors       = false;
    private $fkVisitorsLogins = false;
    private $profileDate      = false;
    private $authID           = false;
    private $totalAgent       = 100;
    private $op               = false;
    private $pkAgent          = false;
    private $dscAgent         = false;
    
    public function __construct() {
        parent::__construct();
        $this->setCredentials();
    }
    public function __destruct() {
        $this->Credentials = null;
        parent::__destruct();
    }
    /**
     * Busca as credenciais do SNAM para acessar a API
     */
    private function setCredentials() {
        $this->Credentials = $this->getJsonToArray(getenv(self::SNAM_ENV));
    }
    /**
     * Função que retorna o token gerado para verificação da autenticidade dos dados recebidos
     * @return string Token gerada para a Vocatio Telecom
     */
    public function getToken() {
        return $this->Credentials['AppVocatioCollect']['vktio_token'];
    }
    /**
     * Função que verifica se o token recebido pela snam é igual ao das credenciais 
     * @param string $Tk Token de verificação da Vocatio Telecom recebido pela snam
     * @return boolean
     */
    public function verifyToken($Tk) {
        if ($Tk == $this->Credentials['AppVocatioCollect']['vktio_token']):
            return true;
        else:
            return false;
        endif; 
    }
    /**
     * Função que retorna uma url para realizar a requisição ao SNAM.io
     * 
     * @param string  $action EndPoint da API do SNAM
     * @return string         retorna a url para enviar a requisição
     */
    private function getUrl($action, $reqID, $getAgents = false) {
        if ($getAgents):
            return str_replace(['{#app#}', '{#action#}', '{#requisition_sid#}'], [$this->Credentials['AppVocatioCollect']['aplicativo'], $action, $reqID], self::SNAM_REQ_URL);
        else:
            return str_replace(['{#app#}', '{#action#}', '{#requisition_sid#}'], [$this->Credentials['AppVocatioCollect']['aplicativo'], $action, $reqID], self::SNAM_URL);
        endif;
    }
    /**
     * Funcção que retorna o nome do usuário para conexão à API da SNAM.io
     * 
     * @return string Nome do usuário SNAM
     */
    private function getUser() {
        return $this->Credentials['AppVocatioCollect']['app_id'];
    }
    /**
     * Função que retorna o token de acesso à APIP da SNAM.io
     * 
     * @return string Token de acesso à APIP da SNAM.io
     */
    private function getSnamToken() {
        return $this->Credentials['AppVocatioCollect']['app_token'];
    }
    private function checkVisitorsTypeProfile($profile) {
        $strWhere = 'where dsc_tpprof = :dscProf'; 
        $strParse = "dscProf={$profile}";
        $select = new Read();
        $select->ExeRead('visitors_type_profiles', $strWhere, $strParse);
        $res = $select->getResult();
        $select = null;
        if ($res['code'] == STT_ERROR):
            parse_str($strParse, $res['params']);
            self::setLogData($res, "[Class SnamWebHook: Erro ao selecionar os dados do tipo do perfil!! (checkVisitorsTypeProfile) ]\n");
        elseif ($res['code'] == STT_OK):
            return $res['data'][0]['pk_visitors_type_profiles'];
        else:
            $res = null;
            $datArr = array('dsc_tpprof' => $profile);
            $insert = new Create();
            $insert->ExeCreate('visitors_type_profiles', $datArr);
            $res = $insert->getResult();
            $insert = null;
            if ($res['code'] == STT_ERROR):
                self::setLogData($res, "[Class SnamWebHook: Erro ao inserir os dados tipo do perfil!! (checkVisitorsTypeProfile) ]\n");
            endif;
            return $res['data']['pk'];
        endif;
    }
    private function getVisitorsAgents() {
        $strWhere = 'where fk_visitors = :fkVisitors and fk_visitors_type_agents = :fkVisitorsTypeAgents'; 
        $strParse = "fkVisitors={$this->pkVisitors}&fkVisitorsTypeAgents={$this->pkAgent}";
        $select = new Read();
        $select->ExeRead('visitors_agents', $strWhere, $strParse);
        $res = $select->getResult();
        $select = null;
        if ($res['code'] == STT_ERROR):
            parse_str($strParse, $res['params']);
            self::setLogData($res, "[Class SnamWebHook: Erro selecionar dados dos agentes!! (getVisitorsAgents) ]\n");
        endif;
        return ($res['code'] == STT_OK);                         
    }
    private function getVisitorsProfiles() {
        $strWhere = "where fk_visitors = :fkVisitors and fk_visitors_logins = :fkVisitorsLogins and fk_visitors_type_agents = :fkVisitorsTypeAgents and fk_visitors_type_profiles = :fkVisitorsTypeProfiles"; 
        $strParse = "fkVisitors={$this->pkVisitors}&fkVisitorsLogins={$this->fkVisitorsLogins}&fkVisitorsTypeAgents={$this->pkAgent}&fkVisitorsTypeProfiles={$this->pkTypeProfile}";
        $select = new Read();
        $select->ExeRead('visitors_profiles', $strWhere, $strParse);
        $res = $select->getResult();
        $select = null;
        if ($res['code'] == STT_ERROR):
            parse_str($strParse, $res['params']);
            self::setLogData($res, "[Class SnamWebHook: Erro ao selecionar o perfil do visitante!! (getVisitorsProfiles) ]\n");
        endif;
        return ($res['code'] == STT_OK);
    }
    private function doInsertVisitorsAgents() {
        $datArr = array(
            'fk_visitors' => $this->pkVisitors, 
            'fk_visitors_logins' => $this->fkVisitorsLogins,
            'fk_visitors_type_agents' => $this->pkAgent, 
            'prof_date' => $this->profileDate,
            'qtd_clicks' => $this->totalAgent,
            'perc_prof' => 100
        );
        $insert = new Create();
        $insert->ExeCreate('visitors_agents', $datArr);
        $res = $insert->getResult();
        $insert = null;
        if ($res['code'] != STT_OK):
            $res['data'] = $datArr;
            self::setLogData($res, "[Class SnamWebHook: Erro ao inserir o Agente do Perfil!! (doInsertVisitorsAgents) ]\n");
        endif;
        return ($res['code'] == STT_OK);
    }
    private function doUpdateVisitorsAgents() {
        $datArr = array(
            'prof_date' => $this->profileDate,
            'qtd_clicks' => $this->totalAgent,
            'perc_prof' => 100
        );
        $strParse = http_build_query($datArr) . "&pkVisitors={$this->pkVisitors}&fkVisitorsLogins={$this->fkVisitorsLogins}&pkAgents={$this->pkAgent}";
        $strWhere = 'where fk_visitors = :pkVisitors and fk_visitors_logins = :fkVisitorsLogins and fk_visitors_type_agents = :pkAgents';
        $update = new Update();
        $update->ExeUpdate('visitors_agents', $datArr, $strWhere, $strParse);
        $res = $update->getResult();
        $update = null;
        if ($res['code'] != STT_OK):
            $res['data'] = $datArr;
            parse_str($strParse, $res['params']);
            self::setLogData($res, "[Class SnamWebHook: Erro ao atualizar o Agente do Perfil!! (doUpdateVisitorsAgents) ]\n");
        endif;
        return ($res['code'] == STT_OK);
    }
    private function doSaveVisitorsAgents() {
        $data = new DateTime('now');
        $this->profileDate = $data->format('Y-m-d H:i:s');
        if ($this->op == 'opInsert'):
           $this->doInsertVisitorsAgents();
        elseif ($this->op == 'opUpdate'):
           $this->doUpdateVisitorsAgents();
        endif;
    }
    private function doInsertVisitorsProfiles($count, $percent) {
        $datArr = array(
            'fk_visitors' => $this->pkVisitors, 
            'fk_visitors_logins' => $this->fkVisitorsLogins,
            'fk_visitors_type_agents' => $this->pkAgent, 
            'fk_visitors_type_profiles' => $this->pkTypeProfile, 
            'qtd_clicks' => $count,
            'perc_prof' => $percent
        );
        $insert = new Create();
        $insert->ExeCreate('visitors_profiles', $datArr);
        $res = $insert->getResult();
        $insert = null;
        if ($res['code'] != STT_OK):
            $res['data'] = $datArr;
            self::setLogData($res, "[Class SnamWebHook: Erro ao inserir o Perfil do Visistante!! (doInsertVisitorsProfiles) ]\n");
        endif;
        return ($res['code'] == STT_OK);
    }
    private function doUpdateVisitorsProfiles($count, $percent) {
        $datArr = array(
            'qtd_clicks' => $count,
            'perc_prof' => $percent
        );
        $strWhere = 'where fk_visitors = :pkVisitors and fk_visitors_logins = :fkVisitorsLogins and fk_visitors_type_agents = :pkTypeAgents and fk_visitors_type_profiles = :pkTypeProfiles';
        $strParse = http_build_query($datArr) . "&pkVisitors={$this->pkVisitors}&fkVisitorsLogins={$this->fkVisitorsLogins}&pkTypeAgents={$this->pkAgent}&pkTypeProfiles={$this->pkTypeProfile}";
        $update = new Update();
        $update->ExeUpdate('visitors_profiles', $datArr, $strWhere, $strParse);
        $res = $update->getResult();
        $update = null;
        if ($res['code'] != STT_OK):
            $res['data'] = $datArr;
            parse_str($strParse, $res['params']);
            self::setLogData($res, "[Class SnamWebHook: Erro ao atualizar dados do Perfil do visitante!! (doUpdateVisitorsProfiles) ]\n");
        endif;
        return ($res['code'] == STT_OK);
    }
    private function doSaveVisitorsProfiles($profile, $count, $percent) {
        $this->pkTypeProfile = $this->checkVisitorsTypeProfile($profile);
        if ($this->pkTypeProfile > 0):
            $this->op = (($this->getVisitorsProfiles()) ? 'opUpdate' : 'opInsert');
            if ($this->op == 'opInsert'):
               $this->doInsertVisitorsProfiles($count, $percent);
            endif;
            if ($this->op == 'opUpdate'):
               $this->doUpdateVisitorsProfiles($count, $percent);
            endif;
        else:
            $this->setResultMessage(STT_ERROR, "Erro ao verificar o tipo de perfil: {$this->pkTypeProfile}");
        endif;
    }
    private function updateDatabase($arr) {
        $this->totalAgent = 0;
        if ($this->pkAgent > 0):
            $this->totalAgent = ((isset($arr['total'])) ? $arr['total']['count'] : 0);
            $this->pkVisitors = ((isset($this->result['curl_response']['external']['id'])) ? $this->result['curl_response']['external']['id'] : false);
            $this->authID = ((isset($this->result['curl_response']['authID'])) ? $this->result['curl_response']['authID'] : false);
            $this->op = (($this->getVisitorsAgents()) ? 'opUpdate' : 'opInsert');
            $this->doSaveVisitorsAgents();
        else:
            $this->setResultMessage(STT_ERROR, "Não posso gravar sem identificar o Agente: [{$this->pkAgent}]");
        endif;
    }
    /**
    * Função que salva os dados do perfil no banco de dados para o usuário corrente
    */
    private function saveProfileIntoDB() {
        // no futuro pegar o nome do agente e o código na tabela visitors_type_agents
        $this->pkAgent = 0;
        $agentsArr = array(1 => 'persona_likes', 2 => 'emotional_context');
        foreach ($agentsArr as $this->pkAgent => $this->dscAgent):
            $arr = ((isset($this->result['curl_response']['agents'][$this->dscAgent])) ? $this->result['curl_response']['agents'][$this->dscAgent] : null);
            if (($arr) && (is_array($arr)) && (count($arr) > 0)):
                $this->updateDatabase($arr); // update user Database with statistc informations of agents and profiles
                foreach ($arr as $key => $value):
                    if ((strtoupper($key) != 'TOTAL') && (is_array($value))):
                        $this->doSaveVisitorsProfiles($key, $value['count'], $value['percent']);
                    endif;
                endforeach;
            endif;
        endforeach;
    }
    private function checkResponse($data) {
        $res = 0;
        if ((isset($data['agents'])) && (isset($data['agents']['persona_likes'])) &&
            (isset($data['agents']['persona_likes']['total'])) &&
            (isset($data['agents']['persona_likes']['total']['count']))):
            $res = $data['agents']['persona_likes']['total']['count'];
        else:
            $res = -1;
        endif;
        return $res;
    }
    private function getReqOptions() {
        return array(
            'method' => 'GET',
            'url' => $this->getUrl('requisition', $this->requisitionID, true),
            'user' => $this->getUser(),
            'pwd' => $this->getSnamToken(),
            'setHeader' => array(
                'content-type: application/json'
            )
        );
    }
    /**
     * Função que faz a consulta da Análise do perfil do usuário gerado pela API da SNAM.io
     * 
     * @param array $data Dados da requisição de retorno da API da SMAN.io
     */
    public function getAnalizedData($data, $x = 0) {
        $this->requisitionID = ((isset($data['request']['requisition_sid'])) ? $data['request']['requisition_sid'] : false);
        $this->fkVisitorsLogins = 'facebook';
        $opts = $this->getReqOptions();
        $this->execCurl($opts);
        if ($this->result['code'] == STT_OK):
            if ((isset($this->result['curl_response'])) && (is_array($this->result['curl_response']))):
                $S = '[Snam.io WebHook: CheckResponse (' . $this->checkResponse($this->result['curl_response']) . ":{$x}) - " . date('j/n/Y H:i:s') . "]\n";
                Base::setLogData(null, $S);
                if (($this->checkResponse($this->result['curl_response']) == 0) && ($x < 5)):
                    sleep(10);
                    $x++;
                    $S = '[Class SnamWebHook: CheckResponse (' . $this->checkResponse($this->result['curl_response']) . ":{$x}) - " . date('j/n/Y H:i:s') . "]\n";
                    self::setLogData($this->result, $S);
                    $this->getAnalizedData($data, $x);
                elseif ($this->checkResponse($this->result['curl_response']) == -1):
                    $this->setResultMessage(STT_ERROR, "[Class SnamWebHook: Não reconheceu os dados (-1:{$x}) - " . date('j/n/Y H:i:s') . "]");
                elseif ($this->checkResponse($this->result['curl_response']) > 0):
                    self::setLogData(null, '[Class SnamWebHook: Salvando Usuário! '. date('j/n/Y H:i:s') . "]\n");
                    $this->saveProfileIntoDB();
                    self::setLogData(null, "[Class SnamWebHook: Usuário Salvo na tentativa {$x} - " . date('j/n/Y H:i:s') . "]\n");
                else:
                    $this->setResultMessage(STT_ERROR, '[Class SnamWebHook: Erro desconhecido - resposta: (' . $this->checkResponse($this->result['curl_response']) . ":{$x}) - " . date('j/n/Y H:i:s') . "]");
                endif;
            endif;
        endif;
    }
}

// init program

$init = new InitPage(json_decode(file_get_contents('php://input'), true), array());
$init->setFilter();
$data = $init->getResult();
if (!isset($data['request']['action'])):
    $data['request'] = $req;
endif;
$init = null;
$verify = new SnamWebHook();
if ($verify->verifyToken($data['request']['verify_token'])):
    if ($data['request']['action'] == 'completed'):
        $verify::setLogData(null, "[Class SnamWebHook: Action: {$data['request']['action']} - " . date('j/n/Y H:i:s') . "]\n");
        $verify->getAnalizedData($data);
    else:
        $verify::setLogData(null, "[Class SnamWebHook: Action: {$data['request']['action']} - " . date('j/n/Y H:i:s') . "]\n");
    endif;
    $res = $verify->getResult();
    Base::setLogData(null, '[SnamWebHook: Perifil Completo!! ' . date('j/n/Y H:i:s') . "]\n");
else:
    $res = Base::setMessage(STT_ERROR, "Verificação de autenticidade: O Sistema que enviou a requisição não pertence à Snam.io!");
    $res['data'] = array(
        "Token Recebido :[{$data['request']['verify_token']}]",
        "Token Aplicação:[" . $verify->getToken() . "]"
    );
endif;
$verify = null;

if ($data['request']['action'] == 'completed'):
    if ($res['code'] == STT_OK):
        Base::setLogData(null, '[SnamWebHook: Iniciar a chamada ao CloudantNoSQL!! ' . date('j/n/Y H:i:s') . "]\n");
        // call cloudant now
        $clNoSQL = new CloudantNoSQL();
        $clNoSQL->getAllUserDataAndSave($res);
        $res = $clNoSQL->getResult();
        $clNoSQL = null;
        if ($res['code'] == STT_OK):
            Base::setLogData(null, '[SnamWebHook: Dados salvos no Cloudant!! ' . date('j/n/Y H:i:s') . "]\n");
        else:
            Base::setLogData($res, '[SnamWebHook: Erro ao salvar no Cloudant!! ' . date('j/n/Y H:i:s') . "]\n");
        endif;
    else:
        Base::setLogData($res, '[SnamWebHook: Error on response - Show Result ' . date('j/n/Y H:i:s') . "]\n");
    endif;
endif;