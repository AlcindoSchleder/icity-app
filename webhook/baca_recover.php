<?php

include_once '../../include/config.inc.php';

const PROFILE_RESULT = array('no_profile' => 0, 'has_agents' => 1, 'has_profile' => 2);
const SQL_CHECK_PROFILE = "select Count(Vag.fk_visitors_type_agents) as qtd_agents, Count(Vpf.fk_visitors_type_profiles) as qtd_profiles 
  from visitors_agents Vag
  left outer join visitors_profiles Vpf
    on Vpf.fk_visitors = Vag.fk_visitors 
   and Vpf.fk_visitors_logins = Vag.fk_visitors_logins
   and Vpf.fk_visitors_type_agents = Vag.fk_visitors_type_agents
 where Vag.fk_visitors = :pkVisitors
   and Vag.fk_visitors_logins = :pkVisitorsLogins";

function checkProfile($pkVisitors) {
    $pkVisitorsLogins = 'facebook';
    $read = new Read();
    $read->FullRead(SQL_CHECK_PROFILE, "pkVisitors={$pkVisitors}&pkVisitorsLogins={$pkVisitorsLogins}");
    $data = $read->getResult();
    $read = null;
    $res = 0;
    if (($data['code'] == STT_OK) && (isset($data['data'])) && (isset($data['data'][0]))):
        if ((isset($data['data'][0]['qtd_agents'])) && ($data['data'][0]['qtd_agents'] > 0)):
            $res++;
        endif;
        if  ((isset($data['data'][0]['qtd_profiles'])) && ($data['data'][0]['qtd_profiles'] > 0)):
            $res++;
        endif;
    endif;
    return $res;
}

function performProfile($data, $limit = null) {
    for ($i = 0; $i < count($data); $i++):
        $res = checkProfile($data[$i]['fk_visitors']);
        echo("Processando dados de {$data[$i]['name_user']} checkProfile = {$res}!<br />");
        if ($res < 2):
            setProfile($data[$i]);
        else:
            echo("Perfil completo!<br />");
        endif;
        if ((isset($limit)) && ($limit == $i)):
            break;
        endif;
        echo("<br />");
    endfor;
}

function setProfile($data) {
    $prof = array(
        'app_userID' => $data['app_user_id'],
        'accessToken' => $data['access_token'],
        'name_user' => $data['name_user'],
        'pk_visitors' => $data['fk_visitors'],
        'app_tp' => $data['pk_visitors_logins'],
        'email_user' => (($data['email'] == 'N/A') ? '' : $data['email'])
    );
    echo("Coletando perfil de {$data['name_user']}!<br />");
    if ((isset($data['app_user_id'])) && (isset($data['access_token']))):
        $colProf = new CollectProfile($prof);
        $colProf->genUserProfile();
        $profile = $colProf->getResult();
        $colProf = null;
        if ($profile['code'] == STT_OK):
            $res = saveProfileID($profile, $data['fk_visitors'], $data['pk_visitors_logins']);
            if ($res['code'] == STT_OK):
                echo("Atualizado perfil de {$data['name_user']}!<br />");
            else:
                echo("Erro: Não Atualizou perfil de {$data['name_user']}: {$res['message']}!<br />");
            endif;
        else:
            echo("Erro ao gerar peril do usuário {$data['name_user']} id da rede social {$data['app_user_id']}!<br />");
        endif;
    else:
        echo("Usuário {$data['name_user']} não tem id da rede social {$data['pk_visitors_logins']} ou access token inválido!<br />");
    endif;
}

function saveProfileID($profile, $fk, $pk) {
    $data = array(
        'auth_id_snam' => $profile['profile']['authID'],
        'request_id_snam' => $profile['profile']['requisitionID']
    );
    $terms = 'where fk_visitors = :fkVisitors and pk_visitors_logins = :pkVisitorsLogin';
    $parseStr = "fkVisitors={$fk}&pkVisitorsLogin={$pk}";
    $updt = new Update();
    $updt->ExeUpdate('visitors_logins', $data, $terms, $parseStr);
    $res = $updt->getResult();
    $updt = null;
    return $res;
}
// init program

echo("Revisa perfis dos usuários!<br />");

$mainSQL = "select * from vw_noprofile";

$read = new Read();
$read->FullRead($mainSQL);
if ($read->getStatus() == STT_OK):
    $data = $read->getResult();
    $read = null;
    performProfile($data['data']);
endif;
