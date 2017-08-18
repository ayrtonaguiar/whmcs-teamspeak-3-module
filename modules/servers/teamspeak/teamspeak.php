<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/lib/Requests.php';
require_once __DIR__ . '/lib/TSDNS.php';
require_once __DIR__ . '/lib/TeamSpeak.php';

function teamspeak_MetaData()
{
    return array(
        'DisplayName'       => 'Módulo Provisionamento TeamSpeak',
        'APIVersion'        => '1.1', // Use API Version 1.1
        'RequiresServer'    => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '10011', // Default Non-SSL Connection Port
    );
}

function teamspeak_ConfigOptions()
{
    return array();
}

function teamspeak_CreateAccount(array $params)
{
    try {

        $settings = Capsule::table('modwhmcs_teamspeak_settings')->where('id', 1)->first();

        $slots = $params['configoptions']['Slots'];

        if ($settings->enabletsdns) {

            if (!isset($params['customfields']['Subdomínio'])) {
                throw new Exception('Falha: Campo personalizado "Subdomínio" não existe');
            }

            $tsdnsClient = new TSDNS($settings->urlapi, $settings->keyapi);

            if (!$tsdnsClient) {
                throw new Exception('Não foi possível se conectar ao servidor TSDNS: ' . $settings->urlapi);
            }

            $request = $tsdnsClient->getZone($params['customfields']['Subdomínio'] . '.' . $settings->domaintsdns);
            $zone = json_decode($request->body);

            if (count($zone->message)) {
                throw new Exception('Subdomínio já existe');
            }
        }

        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);

        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível conectar ao servidor.');
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }

        $port = _modwhmcs_findPort($params, $settings->minport, $settings->maxport);

        if (!$port) {
            throw new Exception('Falha: Não existe portas livres');
        }

        if (!$slots) {
            throw new Exception('Falha: Não foi encontrado o parâmetro "Slots" em opções configuráveis');
        }

        if (!Capsule::table('tblcustomfields')->where('fieldname', 'Token')->where('relid', $params['pid'])->value('id')) {
            throw new Exception('Falha: Campo personalizado "Token" não existe');
        }

        if (!Capsule::table('tblcustomfields')->where('fieldname', 'Porta')->where('relid', $params['pid'])->value('id')) {
            throw new Exception('Falha: Campo personalizado "Porta" não existe');
        }

        $data = array();
        $data['virtualserver_maxclients'] = $slots;
        $data['virtualserver_name'] = $settings->servername;
        $data['virtualserver_autostart'] = true;
        $data['virtualserver_hostbanner_url'] = $settings->bannerlinkurl;
        $data['virtualserver_hostbanner_gfx_url'] = $settings->bannerimgurl;
        $data['virtualserver_hostbanner_mode'] = $settings->bannermode;
        $data['virtualserver_hostbutton_url'] = $settings->buttonlinkurl;
        $data['virtualserver_hostbutton_gfx_url'] = $settings->buttonimgurl;
        $data['virtualserver_hostbutton_tooltip'] = $settings->buttontooltip;
        $data['virtualserver_hostmessage'] = $settings->servermsg;
        $data['virtualserver_hostmessage_mode'] = $settings->servermsgmode;
        $data['virtualserver_welcomemessage'] = $settings->servermsgwelcome;
        $data['virtualserver_port'] = $port;
        $data['virtualserver_download_quota'] = $settings->downloadquota;
        $data['virtualserver_upload_quota'] = $settings->uploadquota;
        $data['virtualserver_download_total_bandwidth'] = $settings->downloadbandwidth;
        $data['virtualserver_upload_total_bandwidth'] = $settings->uploadbandwidth;
        $newserver = $tsAdmin->serverCreate($data);

        if (!$tsAdmin->getElement('success', $newserver)) {
            throw new Exception('Falha: Não foi possível criar o servidor');
        }

        $id1 = Capsule::table('tblcustomfields')->where('fieldname', 'Token')->where('relid', $params['pid'])->value('id');
        Capsule::table('tblcustomfieldsvalues')->where('fieldid', $id1)->where('relid', $params['serviceid'])->update(array('value' => $newserver['data']['token']));

        $id2 = Capsule::table('tblcustomfields')->where('fieldname', 'Porta')->where('relid', $params['pid'])->value('id');
        Capsule::table('tblcustomfieldsvalues')->where('fieldid', $id2)->where('relid', $params['serviceid'])->update(array('value' => $newserver['data']['virtualserver_port']));

        if ($settings->enabletsdns) {
            $tsdnsClient->addZone($params['customfields']['Subdomínio'] . '.' . $settings->domaintsdns, $params['serverip'] . ':' . $port);
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_SuspendAccount(array $params)
{
    try {

        $port = $params['customfields']['Porta'];

        if (!$port) {
            throw new Exception('Falha: Porta não existe');
        }

        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);

        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível conectar ao servidor.');
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Não foi possível encontrar o servidor: ' . $port);
        }

        $data = array();
        $data['virtualserver_autostart'] = 0;
        if (!$tsAdmin->getElement('success', $tsAdmin->serverEdit($data))) {
            throw new Exception('Não foi possível editar o servidor de porta: ' . $port);
        }

        $sid = $tsAdmin->serverIdGetByPort($port);
        $serverstop = $tsAdmin->serverStop($sid['data']['server_id']);

        if (!$tsAdmin->getElement('success', $serverstop)) {
            throw new Exception('Não foi possível desativar o servidor:' . $serverstop['errors'][0]);
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_UnsuspendAccount(array $params)
{
    try {
        $port = $params['customfields']['Porta'];

        if (!$port) {
            throw new Exception('Falha: Porta não existe');
        }

        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);

        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível conectar ao servidor.');
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }

        $sid = $tsAdmin->serverIdGetByPort($port);
        $serverstart = $tsAdmin->serverStart($sid['data']['server_id']);

        if (!$tsAdmin->getElement('success', $serverstart)) {
            throw new Exception('Não foi possível reativar o servidor:' . $serverstart['errors'][0]);
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Não foi possível encontrar o servidor: ' . $port);
        }

        $data = array();
        $data['virtualserver_autostart'] = 1;
        if (!$tsAdmin->getElement('success', $tsAdmin->serverEdit($data))) {
            throw new Exception('Não foi possível editar o servidor de porta: ' . $port);
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_TerminateAccount(array $params)
{
    try {
        $settings = Capsule::table('modwhmcs_teamspeak_settings')->select('enabletsdns', 'domaintsdns', 'keyapi', 'urlapi')->first();
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível se conectar ao servidor.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }
        $sid = $tsAdmin->serverIdGetByPort($port);
        if ($tsAdmin->getElement('success', $tsAdmin->serverDelete($sid['data']['server_id']))) {
            Capsule::table('modwhmcs_teamspeak_backups')->where('port', $port)->delete();
            if ($settings->enabletsdns) {
                $tsdnsClient = new TSDNS($settings->urlapi, $settings->keyapi);
                $tsdnsClient->deleteZone($params['customfields']['Subdomínio'] . '.' . $settings->domaintsdns);
            }
        } else {
            throw new Exception('Não foi possível excluir o servidor.');
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_ChangePackage(array $params)
{
    try {
        $slots = $params['configoptions']['Slots'];
        $port = $params['customfields']['Porta'];
        if (!$port) {
            throw new Exception('Falha: Porta não existe');
        }
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível se conectar ao servidor.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Não foi possível encontrar o servidor: ' . $port);
        }
        $data = array();
        $data['virtualserver_maxclients'] = $slots;
        if (!$tsAdmin->getElement('success', $tsAdmin->serverEdit($data))) {
            throw new Exception('Não foi possível editar o servidor de porta: ' . $port);
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);

        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível se conectar ao servidor.');
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error'   => $errorMsg,
    );
}

function teamspeak_AdminCustomButtonArray()
{
    return array(
        "Iniciar"              => "start_server",
        "Parar"                => "stop_server",
        "Reinstalar"           => "reinstall_server",
        "Restaurar Permissões" => "perm_reset",
    );
}

function teamspeak_ClientAreaCustomButtonArray()
{
    return array(
        "Iniciar Servidor"     => "start_server",
        "Parar Servidor"       => "stop_server",
        "Reinstalar Servidor"  => "reinstall_server",
        "Restaurar Permissões" => "perm_reset",
        "Editar Subdomínio"    => "tsdns",
        "Configurações"        => "settings",
        "Backups"              => "backups",
        "Privilégios"          => "tokens",
        "Proibições"           => "bans",
    );
}

function teamspeak_start_server(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        $serverlist = $tsAdmin->serverList();
        foreach ($serverlist['data'] as $server) {
            if (($server['virtualserver_port'] == $port) && ($server['virtualserver_status'] == 'Online')) {
                throw new Exception('Servidor já encontra-se online.');
            }
        }
        if (!$tsAdmin->getElement('success', $getsid = $tsAdmin->serverIdGetByPort($port))) {
            throw new Exception('Não foi possível iniciar o servidor. Entre em contato com o suporte. (Erro: Servidor ID)');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->serverStart($getsid['data']['server_id']))) {
            throw new Exception('Não foi possível iniciar o servidor. Entre em contato com o suporte (Erro: Iniciar Servidor)');
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_stop_server(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        $serverlist = $tsAdmin->serverList();
        foreach ($serverlist['data'] as $server) {
            if (($server['virtualserver_port'] == $port) && ($server['virtualserver_status'] == 'offline')) {
                throw new Exception('Servidor já encontra-se parado.');
            }
        }
        if (!$tsAdmin->getElement('success', $getsid = $tsAdmin->serverIdGetByPort($port))) {
            throw new Exception('Servidor não encontrado. (Erro: Servidor ID)');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->serverStop($getsid['data']['server_id']))) {
            throw new Exception('Falha 4: Não foi possível parar o servidor. Entre em contato com o suporte (Erro: Parar Servidor)');
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_reinstall_server(array $params)
{
    try {
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        $port = $params['customfields']['Porta'];
        $getsid = $tsAdmin->serverIdGetByPort($port);
        if (!$tsAdmin->getElement('success', $getsid) OR !$tsAdmin->getElement('success', $tsAdmin->serverDelete($getsid['data']['server_id']))) {
            throw new Exception('Problema durante a reinstalação. Entre em contato com o suporte. (Erro: Excluir)');
        }
        $slots = $params['configoptions']['Slots'];
        if (!$slots) {
            throw new Exception('Problema durante a reinstalação. Entre em contato com o suporte. (Erro: Slots)');
        }
        if (!isset($params['customfields']['Token'])) {
            throw new Exception('Problema durante a reinstalação. Entre em contato com o suporte.(Erro: Token)');
        }
        if (!isset($params['customfields']['Porta'])) {
            throw new Exception('Problema durante a reinstalação. Entre em contato com o suporte. (Erro: Porta)');
        }
        $settings = Capsule::table('modwhmcs_teamspeak_settings')->where('id', 1)->first();
        $data = array();
        $data['virtualserver_maxclients'] = $slots;
        $data['virtualserver_name'] = $settings->servername;
        $data['virtualserver_autostart'] = true;
        $data['virtualserver_hostbanner_url'] = $settings->bannerlinkurl;
        $data['virtualserver_hostbanner_gfx_url'] = $settings->bannerimgurl;
        $data['virtualserver_hostbanner_mode'] = $settings->bannermode;
        $data['virtualserver_hostbutton_url'] = $settings->buttonlinkurl;
        $data['virtualserver_hostbutton_gfx_url'] = $settings->buttonimgurl;
        $data['virtualserver_hostbutton_tooltip'] = $settings->buttontooltip;
        $data['virtualserver_hostmessage'] = $settings->servermsg;
        $data['virtualserver_hostmessage_mode'] = $settings->servermsgmode;
        $data['virtualserver_welcomemessage'] = $settings->servermsgwelcome;
        $data['virtualserver_port'] = $port;
        $data['virtualserver_download_quota'] = $settings->downloadquota;
        $data['virtualserver_upload_quota'] = $settings->uploadquota;
        $data['virtualserver_download_total_bandwidth'] = $settings->downloadbandwidth;
        $data['virtualserver_upload_total_bandwidth'] = $settings->uploadbandwidth;

        if (!$tsAdmin->getElement('success', $newserver = $tsAdmin->serverCreate($data))) {
            throw new Exception('Problema durante a reinstalação. Entre em contato com o suporte. (Erro: Criar)');
        }
        $id = Capsule::table('tblcustomfields')->where('fieldname', 'Token')->where('relid', $params['pid'])->value('id');
        Capsule::table('tblcustomfieldsvalues')->where('fieldid', $id)->where('relid', $params['serviceid'])->update(array('value' => $newserver['data']['token']));

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_perm_reset(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Servidor Offline ou indisponível. Entre em contato com o suporte.(Erro: Selecionar)');
        }
        if (!$tsAdmin->getElement('success', $response = $tsAdmin->permReset())) {
            throw new Exception('Não foi possível restaurar as permissões ao padrão. Entre em contato com o suporte.(Erro: Restaurar Permissões)');
        }

        $id = Capsule::table('tblcustomfields')->where('fieldname', 'Token')->where('relid', $params['pid'])->value('id');
        Capsule::table('tblcustomfieldsvalues')->where('fieldid', $id)->where('relid', $params['serviceid'])->update(array('value' => $response['data']['token']));
    } catch (Exception $e) {
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function teamspeak_ClientArea(array $params)
{
    try {
        $settings = Capsule::table('modwhmcs_teamspeak_settings')->select('enabletsdns', 'domaintsdns', 'urlapi', 'keyapi')->first();
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        $hostteamspeak = array();
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            $hostteamspeak['status'] = false;
        } elseif (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            $hostteamspeak['status'] = false;
        } else {
            $hostteamspeak['status'] = true;
        }

        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($params['customfields']['Porta'])) OR !$tsAdmin->getElement('success', $tsAdmin->serverIdGetByPort($params['customfields']['Porta']))) {
            $hostteamspeak['vs']['status'] = false;
        }

        $serverinfo = $tsAdmin->serverInfo();
        if ($serverinfo['data']['virtualserver_status'] != 'online') {
            $hostteamspeak['vs']['status'] = false;
        } else {
            $hostteamspeak['vs']['status'] = true;
        }

        $logs = $tsAdmin->logView(5);
        $logs = $logs['data'];
        $logs1 = array();
        foreach ($logs as $key => $log) {
            $logs1[$key] = explode("|", $log['l']);
            foreach ($logs1 as $key1 => $log1) {
                if ($key1 == 0) {
                    $explodedate = explode(".", $log1[0]);
                    $logs1[$key][0] = $explodedate[0];
                }
            }
            unset($logs1[$key][2]);
            unset($logs1[$key][3]);
        }

        $params['customfields']['Subdominio'] = $params['customfields']['Subdomínio'];

        return array(
            'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
            'templateVariables'              => array(
                'settings'      => get_object_vars($settings),
                'customfields'  => $params['customfields'],
                'logs'          => $logs1,
                'hostteamspeak' => $hostteamspeak,
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables'              => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

function teamspeak_tsdns(array $params)
{

    try {
        $vars['settings'] = Capsule::table('modwhmcs_teamspeak_settings')->select('enabletsdns', 'domaintsdns', 'urlapi', 'keyapi')->first();
        $vars['settings'] = get_object_vars($vars['settings']);
        $params['customfields']['Subdominio'] = $params['customfields']['Subdomínio'];
        $vars['customfields'] = $params['customfields'];

        if (isset($_GET['ma']) && $_GET['ma'] == 'editzone') {
            if (empty($_GET['zone'])) {
                throw new Exception('Não foi informada nenhuma zona.');
            }

            if ($_GET['zone'] == $_GET['oldzone']) {
                throw new Exception('Há zona informada é a mesma que a anterior.');
            }

            $settings = Capsule::table('modwhmcs_teamspeak_settings')->select('enabletsdns', 'domaintsdns', 'urlapi', 'keyapi')->first();
            $tsdnsClient = new TSDNS($settings->urlapi, $settings->keyapi);
            $response = $tsdnsClient->getZone($_GET['zone'] . "." . $settings->domaintsdns);
            $result = json_decode($response->body);

            if (count($result->message)) {
                throw new Exception('Há zona informada já existe.');
            }
            $tsdnsClient->deleteZone($_GET['oldzone'] . "." . $settings->domaintsdns);
            $response = $tsdnsClient->addZone($_GET['zone'] . "." . $settings->domaintsdns, $params['serverip'] . ":" . $params['customfields']['Porta']);
            $result = json_decode($response->body);

            if (count($result->message)) {
                throw new Exception('Não foi possível editar a zona.');
            } else {
                Capsule::table('tblcustomfieldsvalues')->where('value', $params['customfields']['Subdomínio'])->update(array('value' => $_GET['zone']));

                return 'success';
            }
        }

        return array(
            'templatefile' => 'templates/tsdns',
            'vars'         => $vars,
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return $e->getMessage();
    }
}

function teamspeak_backups(array $params)
{
    try {
        $vars['backups'] = Capsule::table('modwhmcs_teamspeak_backups')->where('port', $params['customfields']['Porta'])->get();
        foreach ($vars['backups'] as $key => $backup) {
            $vars['backups'][$key] = get_object_vars($backup);
        }
        if (!empty($_GET['custom'])) {
            switch ($_GET['custom']) {
                case 'create':
                    $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
                    if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
                        throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
                    }
                    if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
                        throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
                    }
                    if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($params['customfields']['Porta']))) {
                        throw new Exception('Servidor desligado ou não existe');
                    }
                    $getsid = $tsAdmin->serverIdGetByPort($params['customfields']['Porta']);
                    $snapshot = $tsAdmin->serverSnapshotCreate();
                    Capsule::table('modwhmcs_teamspeak_backups')->insert(array('sid' => $getsid['data']['server_id'], 'port' => $params['customfields']['Porta'], 'data' => ltrim($snapshot['data']), 'date' => date("Y-m-d H:i:s")));
                    break;
                case 'download':
                    header('Content-type: text/plain');
                    header('Content-Disposition: attachment; backup' . date("Y-m-d") . '.txt');
                    $data = Capsule::table('modwhmcs_teamspeak_backups')->where('id', $_GET['backupid'])->value('data');
                    echo $data;
                    exit();
                    break;
                case 'restore':
                    $backup = Capsule::table('modwhmcs_teamspeak_backups')->where('id', $_GET['backupid'])->value('data');
                    $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
                    if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
                        throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
                    }
                    if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
                        throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
                    }
                    if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($params['customfields']['Porta']))) {
                        throw new Exception('Servidor desligado ou não existe');
                    }
                    if (!$tsAdmin->getElement('success', $tsAdmin->serverSnapshotDeploy($backup))) {
                        throw new Exception('Não foi possível restaurar o backup');
                    }

                    break;
                case 'delete':
                    Capsule::table('modwhmcs_teamspeak_backups')->where('id', $_GET['backupid'])->delete();
                    break;
                default:
                    return null;
                    break;
            }

            return 'success';
        }

        return array(
            'templatefile' => 'templates/backups',
            'vars'         => $vars,
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return $e->getMessage();
    }
}

function teamspeak_tokens(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Servidor desligado ou não existe');
        }
        $response = $tsAdmin->tokenList();
        $vars['tokens'] = $response['data'];
        $serverglist = $tsAdmin->serverGroupList();
        $sglist = array();
        foreach ($serverglist['data'] as $sg) {
            if ($sg['type'] == 1) {
                $sglist[] = $sg;
            }
        }
        $vars['sglist'] = $sglist;
        if (!empty($_GET['custom'])) {
            switch ($_GET['custom']) {
                case 'create':
                    if (!$tsAdmin->getElement('success', $tsAdmin->tokenAdd(0, $_GET['groupid'], 0, $_GET['desc']))) {
                        throw new Exception('Impossível criar o token. Entre em contato com o suporte. (Erro: Adic. Token)');
                    }
                    break;
                case 'delete':
                    if (!$tsAdmin->getElement('success', $tsAdmin->tokenDelete($_GET['token']))) {
                        throw new Exception('Impossível excluir o token. Entre em contato com o suporte. (Erro: Excluir Token)');
                    }

                    break;
                default:
                    return null;
                    break;
            }

            return 'success';
        }

        return array(
            'templatefile' => 'templates/tokens',
            'vars'         => $vars,
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return $e->getMessage();
    }
}

function teamspeak_bans(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Servidor indisponível. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Impossível conectar ao servidor. Entre em contato com o suporte.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Servidor desligado ou não existe');
        }
        $response = $tsAdmin->banList();
        $vars['bans'] = $response['data'];
        if (!empty($_GET['custom'])) {
            switch ($_GET['custom']) {
                case 'create':
                    if (isset($_GET['bantype'])) {
                        switch ($_GET['bantype']) {
                            case 'ip':
                                if (filter_input(INPUT_GET, $_GET['inu'], FILTER_VALIDATE_IP) === false) {
                                    throw new Exception('IP informado não é válido.');
                                }
                                if (!$tsAdmin->getElement('success', $tsAdmin->banAddByIp($_GET['inu'], 0, $_GET['reason']))) {
                                    throw new Exception('Impossível criar o ban. Entre em contato com o suporte. (Erro: Adic. Token)');
                                }
                                break;
                            case 'name':
                                if (!$tsAdmin->getElement('success', $tsAdmin->banAddByName($_GET['inu'], 0, $_GET['reason']))) {
                                    throw new Exception('Impossível criar o ban. Entre em contato com o suporte. (Erro: Adic. Token)');
                                }
                                break;
                            case 'uid':
                                if (!$tsAdmin->getElement('success', $tsAdmin->banAddByUid($_GET['inu'], 0, $_GET['reason']))) {
                                    throw new Exception('Impossível criar o ban. Entre em contato com o suporte. (Erro: Adic. Token)');
                                }
                                break;
                        }
                    }

                    return 'success';
                    break;
                case 'delete':
                    if (!$tsAdmin->getElement('success', $tsAdmin->banDelete($_GET['banid']))) {
                        throw new Exception('Impossível excluir o ban. Entre em contato com o suporte. (Erro: Excluir Token)');
                    }

                    return 'success';
                    break;
                default:
                    return null;
                    break;
            }
        }

        return array(
            'templatefile' => 'templates/bans',
            'vars'         => $vars,
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return $e->getMessage();
    }
}

function teamspeak_settings(array $params)
{
    try {
        $port = $params['customfields']['Porta'];
        if (!$port) {
            throw new Exception('Porta não existe');
        }
        if ($_GET['pw'] !== $_GET['confirmpw']) {
            throw new Exception('As senhas informadas não coincidem.');
        }
        $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
        if (!$tsAdmin->getElement('success', $tsAdmin->connect())) {
            throw new Exception('Não foi possível se conectar ao servidor.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->login($params['serverusername'], $params['serverpassword']))) {
            throw new Exception('Login com serverquery mal-sucedido.');
        }
        if (!$tsAdmin->getElement('success', $tsAdmin->selectServer($port))) {
            throw new Exception('Não foi possível encontrar o servidor: ' . $port);
        }
        if (!$tsAdmin->getElement('success', $serverinfo = $tsAdmin->serverInfo())) {
            throw new Exception('Sem informações do servidor.');
        }
        $vars['serverinfo'] = $serverinfo['data'];
        if (isset($_GET['custom']) && $_GET['custom'] == 'save') {
            $data = array();
            $data['virtualserver_name'] = $_GET['hostname'];
            $data['virtualserver_welcomemessage'] = $_GET['welcomemessage'];
            $data['virtualserver_password'] = $_GET['pw'];
            if (!$tsAdmin->getElement('success', $tsAdmin->serverEdit($data))) {
                throw new Exception('Não foi possível mudar a senha. Entre em contato com o suporte. (Erro: Senha)');
            }

            return 'success';
        }

        return array(
            'templatefile' => 'templates/settings',
            'vars'         => $vars,
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'teamspeak',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

}

function _modwhmcs_findPort($params, $startp, $endp)
{
    $tsAdmin = new TeamSpeak($params['serverip'], $params['serverport']);
    $tsAdmin->connect();
    $tsAdmin->login($params['serverusername'], $params['serverpassword']);
    $tport = $startp;
    while ($tport <= $endp) {
        if (!$tsAdmin->getElement('success', $tsAdmin->serverIdGetByPort($tport))) {
            return $tport;
        }
        ++$tport;
    }

    return 0;
}