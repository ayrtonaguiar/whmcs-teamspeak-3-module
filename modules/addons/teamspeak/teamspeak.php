<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use Illuminate\Database\Capsule\Manager as Capsule;

require_once '../../servers/teamspeak/lib/Requests.php';
require_once '../../servers/teamspeak/lib/TSDNS.php';
require_once '../../servers/teamspeak/lib/TeamSpeak.php';
require_once __DIR__ . '/lib/functions.php';
function teamspeak_config()
{
    $configarray = array(
        "name" => "Módulo TeamSpeak",
        "description" => "Módulo adicional para o provisionamento de servidor TeamSpeak",
        "version" => "1.0",
        "author" => "ModWHMCS",
        "language" => "portuguese",
    );
    return $configarray;
}

function teamspeak_activate()
{
    try {
        if (!Capsule::schema()->hasTable('modwhmcs_teamspeak_backups')) {
            Capsule::schema()->create('modwhmcs_teamspeak_backups', function ($table) {
                $table->increments('id');
                $table->integer('sid')->nullable();
                $table->integer('port');
                $table->mediumText('data')->nullable();
                $table->timestamp('date')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('modwhmcs_teamspeak_settings')) {
            Capsule::schema()->create('modwhmcs_teamspeak_settings', function ($table) {
                $table->increments('id');
                $table->mediumInteger('minport');
                $table->mediumInteger('maxport');
                $table->mediumText('servername')->nullable();
                $table->longText('servermsgwelcome')->nullable();
                $table->longText('servermsg')->nullable();
                $table->integer('servermsgmode');
                $table->longText('bannerlinkurl')->nullable();
                $table->longText('bannerimgurl')->nullable();
                $table->integer('bannermode');
                $table->longText('buttonlinkurl')->nullable();
                $table->longText('buttonimgurl')->nullable();
                $table->longText('buttonmsgtooltip')->nullable();
                $table->bigInteger('uploadquota');
                $table->bigInteger('downloadquota');
                $table->bigInteger('uploadbandwidth');
                $table->bigInteger('downloadbandwidth');
                $table->integer('enabletsdns');
                $table->longText('domaintsdns')->nullable();
                $table->longText('urlapi')->nullable();
                $table->longText('keyapi')->nullable();
            });
            Capsule::table('modwhmcs_teamspeak_settings')->insert(array('minport' => 9000, 'maxport' => 9999, 'servername' => 'TeamSpeak ]I[ Server', 'servermsgwelcome' => 'Welcome to TeamSpeak, check [URL]www.teamspeak.com[/URL] for latest information', 'servermsgmode' => 0, 'bannermode' => 0, 'uploadquota' => -1, 'downloadquota' => -1, 'uploadbandwidth' => -1, 'downloadbandwidth' => -1, 'enabletsdns' => 0));

        }
    } catch (Exception $e) {
        return array('status' => 'error', 'description' => 'Não foi possível criar a tabela. Verifique seu banco de dados');
    }
    return array('status' => 'success');
}

function teamspeak_deactivate()
{
    try {
        Capsule::schema()->dropIfExists('modwhmcs_teamspeak_settings');
    } catch (Exception $e) {
        return array('status' => 'error', 'description' => 'Não foi possível excluir a tabela. Verifique seu banco de dados');
    }
    return array('status' => 'success');
}

function teamspeak_output($vars)
{
    $license = Capsule::table('tbladdonmodules')->where('module', 'teamspeak')->where('setting', 'license')->value('value');
    $listServers = Capsule::table('tblservers')->where('type', 'teamspeak')->get();
    $servers = array();
    foreach ($listServers as $key => $server) {
        $servers[$key] = new stdClass();
        $servers[$key]->name = $server->name;
        $servers[$key]->ipaddress = $server->ipaddress;
        $servers[$key]->port = ($server->port ? $server->port : 10011);
        $servers[$key]->username = $server->username;
        $servers[$key]->password = $server->password;
    }
    $alert = array();
    $names = array();
    foreach ($servers as $server) {
        $names[$server->name] = md5($server->name);
    }
    $output = '';
    if (!isset($_GET['display'])) {
        $output .= "<script src='../modules/addons/teamspeak/js/script.js'></script><script type=\"text/javascript\">
        $(document).ready(function () {
            var servers = " . json_encode($names) . ";
            var server, hash;
            for (var key in servers) {
                server = key;
                hash = servers[key];
                test(server, hash);
                (function loop(server, hash) {
                    setTimeout(function () {
                        test(server, hash);
                        loop(server, hash);
                    }, 10000);
                })(server, hash);
            }
        });    
    </script>";
    }
    if (isset($_POST['action']) && $_POST['action'] == 'savesettings') {
        try {
            if (!Capsule::table('modwhmcs_teamspeak_settings')->where('id', 1)->update(array('minport' => $_POST['minport'], 'maxport' => $_POST['maxport'], 'servername' => $_POST['servername'], 'servermsgwelcome' => $_POST['servermsgwelcome'], 'servermsg' => $_POST['servermsg'], 'servermsgmode' => $_POST['servermsgmode'], 'bannerlinkurl' => $_POST['bannerlinkurl'], 'bannerimgurl' => $_POST['bannerimgurl'], 'bannermode' => $_POST['bannermode'], 'buttonlinkurl' => $_POST['buttonlinkurl'], 'buttonimgurl' => $_POST['buttonimgurl'], 'buttonmsgtooltip' => $_POST['buttonmsgtooltip'], 'uploadquota' => $_POST['uploadquota'], 'downloadquota' => $_POST['downloadquota'], 'uploadbandwidth' => $_POST['uploadbandwidth'], 'downloadbandwidth' => $_POST['downloadbandwidth'], 'enabletsdns' => $_POST['enabletsdns'], 'domaintsdns' => $_POST['domaintsdns'], 'urlapi' => $_POST['urlapi'], 'keyapi' => $_POST['keyapi']))) {
                throw new Exception('Não possível salvar as configurações');
            }
        } catch (Exception $e) {
            $alert['status'] = 'error';
            $alert['alert'] = $e->getMessage();
        }
        $alert['status'] = 'success';
        $alert['alert'] = 'As configurações foram salvas';
    }
    $version = json_decode(file_get_contents('https://www.modwhmcs.com/d/modupdate.php'));
    $output .= "<link href='../modules/addons/teamspeak/css/style.css' rel='stylesheet'/><div class='panel panel-default teamspeak'><div class='panel-heading'><span class='pull-right verify-status' style='display: none'><i class='fa fa-spinner fa-spin'></i>&nbsp;&nbsp;Verificando Status dos Servidores</span></div><div class='panel-body'><div class='row'><div class='col-sm-6'><div class='head'><div class='icon'><i class='fa fa-cogs'></i></div><div class='head-name'>TeamSpeak&nbsp;&nbsp;</div></div></div><div class='col-sm-6'><div class='row version-status'><div class='col-sm-4'><div class='panel panel-info'><div class='panel-heading text-center'>Versão Instalada</div><div class='panel-body text-center'><strong class='text-info'>{$vars['version']}</strong></div></div></div>";

    if (!isset($_GET['display'])) {
        $output .= "";
        $output .= "<div class='row'><div class='col-sm-12'><ul class=\"nav nav-tabs\" role=\"tablist\"><li role=\"presentation\" " . ($_POST['action'] == 'savesettings' ? '' : 'class="active"') . "><a href=\"#servers\" aria-controls=\"servers\" role=\"tab\" data-toggle=\"tab\">Servidores</a></li><li role=\"presentation\" " . (!$_POST['action'] == 'savesettings' ? '' : 'class="active"') . "><a href=\"#settings\" aria-controls=\"settings\" role=\"tab\" data-toggle=\"tab\">Configurações</a></li></ul><div class=\"tab-content\"><div role=\"tabpanel\" class=\"tab-pane" . ($_POST['action'] == 'savesettings' ? '' : ' active') . "\" id=\"servers\"><div class=\"table-responsive\"><table class='table table-condensed'><thead><tr><th class='text-center'>#</th><th>Nome do Servidor</th><th>IP</th><th>Porta ServerQuery</th><th><i class='fa fa-cogs'></i>&nbsp; Gerenciar</th></tr></thead><tbody>";
        $i = 1;
        foreach ($servers as $server) {
            $output .= "<tr id='" . md5($server->name) . "'><td class='text-center'>{$i}</td>";
            $output .= "<td>{$server->name}</td>";
            $output .= "<td>{$server->ipaddress}</td>";
            $output .= "<td>{$server->port}</td>";
            $output .= "<td><span class='manager show invisible'><a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$server->ipaddress}' class='btn btn-primary btn-xs'><i class='fa fa-server'></i>&nbsp; TeamSpeak</a>&nbsp;<a href='addonmodules.php?module=teamspeak&amp;display=tsdns&amp;serverip={$server->ipaddress}' class='btn btn-danger btn-xs'><i class='fa fa-link'></i>&nbsp; TSDNS</a></span></td></tr>";
            $i++;
        }
        $output .= "</tbody></table></div></div><div role=\"tabpanel\" class=\"tab-pane" . (!$_POST['action'] == 'savesettings' ? '' : ' active') . "\" id=\"settings\"><div class='alert alert-info'>As modificações feitas, só serão aplicadas aos novos servidores, os atuais permaneceram com as configurações já definidas.<i class='fa fa-info fa-2x pull-left' style='padding:5px'></i></div>";
        $settings = Capsule::table('modwhmcs_teamspeak_settings')->first();
        switch ($settings->servermsgmode) {
            case 1:
                $selectm1 = 'selected';
                break;
            case 2:
                $selectm2 = 'selected';
                break;
            case 3:
                $selectm3 = 'selected';
                break;
            default:
                $selectm0 = 'selected';
                break;
        }
        switch ($settings->bannermode) {
            case 1:
                $selectb1 = 'selected';
                break;
            case 2:
                $selectb2 = 'selected';
                break;
            default:
                $selectb0 = 'selected';
                break;
        }
        if ($settings->enabletsdns) {
            $check1 = 'checked';
        } else {
            $check0 = 'checked';
        }
        if (!empty($alert)) {
            $output .= "<div class='row'><div class='col-md-6 col-md-offset-3'>";
            if ($alert['status'] == 'error') {
                $output .= "<div class='alert alert-danger'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><p><strong><i class='fa fa-frown-o fa-4x pull-left'></i>Oops! Algo ocorreu!</strong></p>{$alert['alert']}</div>";
            } else {
                $output .= "<div class='alert alert-success'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><p><strong><i class='fa fa-smile-o fa-4x pull-left'></i>Parabéns! Deu tudo certo!</strong></p>{$alert['alert']}</div>";
            }
            $output .= "</div></div>";
        }
        $output .= "<form class=\"form-horizontal\" method='post' action='addonmodules.php?module=teamspeak'>
<input type='hidden' name='module' value='teamspeak'>
<input type='hidden' name='action' value='savesettings'>
<div class='row'>
<div class='col-md-8 col-md-offset-2'>
<fieldset>
  <legend class='text-center'>Servidor</legend>
  <div class=\"form-group\">
    <label for=\"minport\" class=\"col-sm-3 control-label\">Porta Miníma</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='minport' id=\"minport\" value='{$settings->minport}'>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"maxport\" class=\"col-sm-3 control-label\">Porta Máxima</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='maxport' id=\"maxport\" value='{$settings->maxport}'>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"servername\" class=\"col-sm-3 control-label\">Nome do Servidor</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='servername' id=\"servername\" value='{$settings->servername}'>
      <span class=\"help-block\">Será o nome do servidor (Deixe em branco para padrão)</span>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"servermsgwelcome\" class=\"col-sm-3 control-label\">Mensagem de Boas-vindas</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='servermsgwelcome' id=\"servermsgwelcome\" value='{$settings->servermsgwelcome}'>
      <span class='help-block'>Aparecerá no chat sempre que um usuário entrar</span>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"servermsg\" class=\"col-sm-3 control-label\">Mensagem do Servidor</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='servermsg' id=\"servermsg\" value='{$settings->servermsg}'>
      <span class='help-block'>Está mensagem irá aparecer quando o usuário entrar no servidor (Deixe em branco para não aparecer)</span>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"servermsgmode\" class=\"col-sm-3 control-label\">Modo de Exibição</label>
    <div class=\"col-sm-9\">    
      <select name='servermsgmode' id=\"servermsgmode\" class=\"form-control\"><option value='0' {$selectm0}>Não mostrar mensagem</option><option value='1' {$selectm1}>Mostrar mensagem no chat</option><option value='2' {$selectm2}>Mostrar mensagem em um modal</option><option value='3' [$selectm3]>Mostrar mensagem no modal e sair</option></select>
    </div>
  </div>
  </fieldset>
  <fieldset>
  <legend class='text-center'>Banner</legend>
  <div class=\"form-group\">
    <label for=\"bannerlinkurl\" class=\"col-sm-3 control-label\">Link URL</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='bannerlinkurl' id=\"bannerlinkurl\" value=\"{$settings->bannerlinkurl}\">
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"bannerimgurl\" class=\"col-sm-3 control-label\">Imagem URL</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='bannerimgurl' id=\"bannerimgurl\" value=\"{$settings->bannerimgurl}\">
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"bannermode\" class=\"col-sm-3 control-label\">Modo de Exibição</label>
    <div class=\"col-sm-9\">
    <select name='bannermode' id=\"bannermode\" class=\"form-control\"><option value='0' {$selectb0}>Não redimensionar</option><option value='1' {$selectb1}>Redimensionar ignorando o aspecto</option><option value='2' {$selectb2}>Redimensionar mantendo o aspecto</option></select>
    </div>
  </div>
  </fieldset>
  <fieldset>
  <legend class='text-center'>Botão</legend>
  <div class=\"form-group\">
    <label for=\"buttonlinkurl\" class=\"col-sm-3 control-label\">Link URL</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='buttonlinkurl' id=\"buttonlinkurl\" value=\"{$settings->buttonlinkurl}\">
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"buttonimgurl\" class=\"col-sm-3 control-label\">Imagem URL</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='buttonimgurl' id=\"buttonimgurl\" value=\"{$settings->buttonimgurl}\">
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"buttonmsgtootlip\" class=\"col-sm-3 control-label\">Mensagem Tooltip</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='buttonmsgtooltip' id=\"buttonmsgtooltip\" value=\"{$settings->buttonmsgtooltip}\">
    </div>
  </div>
  </fieldset>
  <fieldset>
  <legend class='text-center'>Transferência de Arquivos</legend>
  <div class=\"form-group\">
    <label for=\"uploadquota\" class=\"col-sm-3 control-label\">Upload Cota</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='uploadquota' id=\"uploadquota\" value=\"{$settings->uploadquota}\">
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"downloadquota\" class=\"col-sm-3 control-label\">Download Cota</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='downloadquota' id=\"downloadquota\" value=\"{$settings->downloadquota}\">
    </div>
  </div>  
  <div class=\"form-group\">
    <label for=\"uploadbandwidth\" class=\"col-sm-3 control-label\">Velocidade Máx de Upload</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='uploadbandwidth' id=\"uploadbandwidth\" value=\"{$settings->uploadbandwidth}\">
    </div>
  </div>  
  <div class=\"form-group\">
    <label for=\"downloadbandwidth\" class=\"col-sm-3 control-label\">Velocidade Máx de Download</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='downloadbandwidth' id=\"downloadbandwidth\" value=\"{$settings->downloadbandwidth}\">
    </div>
  </div>
  </fieldset>  
  <fieldset>
  <legend class='text-center'>TeamSpeak DNS</legend>
  <div class=\"form-group\">
    <label class=\"col-sm-3 control-label\">Ativar TSDNS</label>
    <div class=\"col-sm-9\">
  <label class=\"radio-inline\">
  <input type=\"radio\" name=\"enabletsdns\" id=\"1\" value=\"1\" {$check1}> Sim
</label>
<label class=\"radio-inline\">
  <input type=\"radio\" name=\"enabletsdns\" id=\"0\" value=\"0\" {$check0}> Não
</label>
</div>
</div>
  <div class=\"form-group\">
    <label for=\"domaintsdns\" class=\"col-sm-3 control-label\">Domínio TSDNS</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='domaintsdns' id=\"domaintsdns\" value=\"{$settings->domaintsdns}\">
      <span class=\"help-block\">Exemplo: example.com.br</span>
    </div>
  </div> 
  <div class=\"form-group\">
    <label for=\"urlapi\" class=\"col-sm-3 control-label\">Endereço API</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='urlapi' id=\"urlapi\" value=\"{$settings->urlapi}\">
      <span class=\"help-block\">Exemplo: http://1.2.3.4:3000</span>
    </div>
  </div>
  <div class=\"form-group\">
    <label for=\"keyapi\" class=\"col-sm-3 control-label\">Chave API</label>
    <div class=\"col-sm-9\">
      <input type=\"text\" class=\"form-control\" name='keyapi' id=\"keyapi\" value=\"{$settings->keyapi}\">
    </div>
  </div>
  </fieldset>
<hr>
  <div class=\"form-group\">
    <div class=\"col-sm-offset-2 col-sm-8\">
      <button type=\"submit\" class=\"btn btn-default center-block\"><i class='fa fa-floppy-o'></i>&nbsp;&nbsp;Salvar</button>
    </div>
  </div>
  </div>
  </div>
</form></div></div></div></div>";
    } elseif (isset($_GET['display'])) {
        switch ($_GET['display']) {
            case 'virtualservers':
                $serververif = '';
                foreach ($servers as $server) {
                    if ($server->ipaddress == $_GET['serverip']) {
                        $command = "decryptpassword";
                        $values["password2"] = $server->password;

                        $results1 = localAPI($command, $values);
                        $server->password = $results1['password'];
                        $serververif = $server;
                    }
                }
                $tsAdmin = new TeamSpeak($serververif->ipaddress, $serververif->port);
                $tsAdmin->connect();
                $tsAdmin->login($serververif->username, $serververif->password);
                if (isset($_GET['action'], $_GET['sid'])) {
                    switch ($_GET['action']) {
                        case 'start':
                            if (!$tsAdmin->getElement('success', $status = $tsAdmin->serverStart($_GET['sid']))) {
                                $alert['status'] = "error";
                                $alert['alert'] = "Não foi possível iniciar o servidor.";
                            } else {
                                $alert['status'] = "success";
                                $alert['alert'] = "Servidor foi iniciado.";
                            }
                            break;
                        case 'stop':
                            if (!$tsAdmin->getElement('success', $status = $tsAdmin->serverStop($_GET['sid']))) {
                                $alert['status'] = "error";
                                $alert['alert'] = "Não foi possível desligar o servidor.";
                            } else {
                                $alert['status'] = "success";
                                $alert['alert'] = "Servidor foi desligar.";
                            }
                            break;
                        case 'delete':
                            if (!$tsAdmin->getElement('success', $status = $tsAdmin->serverDelete($_GET['sid']))) {
                                $alert['status'] = "error";
                                $alert['alert'] = "Não foi possível excluir o servidor.";
                            } else {
                                $alert['status'] = "success";
                                $alert['alert'] = "Servidor foi excluído.";
                            }
                            break;
                            break;
                        default :
                            break;
                    }
                    if (!empty($alert)) {
                        $output .= "<div class='row'><div class='col-md-6 col-md-offset-3'>";
                        if ($alert['status'] == 'error') {
                            $output .= "<div class='alert alert-danger'><a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$_GET['serverip']}' class='btn btn-danger pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Lista</a><p><strong><i class='fa fa-frown-o fa-4x pull-left'></i>Oops! Algo ocorreu!</strong></p>{$alert['alert']}</div>";
                        } else {
                            $output .= "<div class='alert alert-success'><a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$_GET['serverip']}' class='btn btn-success pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Lista</a><p><strong><i class='fa fa-smile-o fa-4x pull-left'></i>Parabéns! Deu tudo certo!</strong></p>{$alert['alert']}</div>";
                        }
                        $output .= "</div></div>";
                    }
                } else {
                    $serverlist = $tsAdmin->serverList();
                    $output .= "<a href='addonmodules.php?module=teamspeak' class='btn btn-info pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Página Inicial</a><div class='clearfix'></div><br><div class='table-responsive'><table class='table'><thead><tr><th>#</th><th><i class='fa fa-server'></i>&nbsp;&nbsp;Nome do Servidor</th><th><i class='fa fa-plug'></i>&nbsp;&nbsp;Porta</th><th><i class='fa fa-users'></i>&nbsp;&nbsp;Slots</th><th><i class='fa fa-bar-chart'></i>&nbsp;&nbsp;Status</th><th><i class='fa fa-clock-o'></i>&nbsp;&nbsp;Uptime</th><th><i class='fa fa-cogs'></i>&nbsp;&nbsp;Gerenciar</th></tr></thead><tbody>";
                    if ($serverlist['data']) {
                        foreach ($serverlist['data'] as $server) {
                            $output .= "<tr><td>{$server['virtualserver_id']}</td><td>{$server['virtualserver_name']}</td><td>{$server['virtualserver_port']}</td><td>{$server['virtualserver_maxclients']}</td><td>" . ucfirst($server['virtualserver_status']) . "</td><td>" . secondsToTime($server['virtualserver_uptime']) . "</td><td>";
                            if ($server['virtualserver_status'] != 'online') {
                                $output .= "<a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$serververif->ipaddress}&amp;action=start&amp;sid={$server['virtualserver_id']}' class='btn btn-success btn-xs'><i class='fa fa-play'></i>&nbsp;&nbsp;Iniciar</a>";
                            } else {
                                $output .= "<a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$serververif->ipaddress}&amp;action=stop&amp;sid={$server['virtualserver_id']}' class='btn btn-warning btn-xs'><i class='fa fa-stop'></i>&nbsp;&nbsp;Parar</a>";
                            }
                            $output .= "&nbsp;<a href='addonmodules.php?module=teamspeak&amp;display=virtualservers&amp;serverip={$serververif->ipaddress}&amp;action=delete&amp;sid={$server['virtualserver_id']}' class='btn btn-danger btn-xs'><i class='fa fa-times'></i>&nbsp;&nbsp;Excluir</a>";
                            $output .= "</td></tr>";
                        }
                    } else {
                        $output .= "<tr><td colspan='4' class='text-center'>Não foi encontrada nenhum servidor para este IP!</td></tr>";
                    }
                    $output .= "</table></div>";
                }
                break;
            case 'tsdns':
                $settings = Capsule::table('modwhmcs_teamspeak_settings')->select('urlapi', 'keyapi')->first();
                try {
                    $tsdnsClient = new TSDNS($settings->urlapi, $settings->keyapi);
                    $request = $tsdnsClient->getZones();
                    $serverstsdns = json_decode($request->body);
                    $serverscurrent = array();
                    foreach ($serverstsdns->message as $server) {
                        $serverip = explode(':', $server->target);
                        if ($serverip[0] == $_GET['serverip']) {
                            $serverscurrent[] = $server;
                        }
                    }
                    if (isset($_GET['action'], $_GET['zone'])) {

                        switch ($_GET['action']) {
                            case 'editzone' :
                                foreach ($serverscurrent as $server) {
                                    if ($server->zone == $_GET['zone']) {
                                        throw new Exception('Há zona informada já existe.');
                                    }
                                }
                                $tsdnsClient->deleteZone($_GET['oldzone']);
                                $result = $tsdnsClient->addZone($_GET['zone'], $_GET['target']);
                                if (!$result->success) {
                                    throw new Exception('Não foi possível editar a zona.');
                                } else {
                                    $alert['status'] = 'success';
                                    $alert['alert'] = 'Zona foi editada.';
                                }
                                break;
                            case 'delzone' :
                                $result = $tsdnsClient->deleteZone($_GET['zone']);
                                if (!$result->success) {
                                    throw new Exception('Não foi possível excluir a zona.');
                                } else {
                                    $alert['status'] = 'success';
                                    $alert['alert'] = 'Zona foi excluída.';
                                }
                                break;
                            default:
                                break;
                        }
                    } else {
                        $output .= "<a href='addonmodules.php?module=teamspeak' class='btn btn-info pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Página Inicial</a><div class='clearfix'></div><br><div class='table-responsive'><table class='table table-condensed'><thead><tr><th>#</th><th><i class='fa fa-globe'></i>&nbsp;Zona</th><th><i class='fa fa-dot-circle-o'></i>&nbsp;Destino</th><th><i class='fa fa-cogs'></i>&nbsp;Gerenciar</th></tr></thead><tbody>";
                        if ($serverscurrent) {
                            foreach ($serverscurrent as $server) {
                                $output .= "<tr><td>{$server->id}</td><td>{$server->zone}</td><td>{$server->target}</td><td><a class=\"btn btn-primary btn-xs\" role=\"button\" data-toggle=\"modal\" data-target=\"#" . md5($server->zone) . "\" aria-expanded=\"false\" aria-controls=\"" . md5($server->zone) . "\"><i class='fa fa-pencil-square-o'></i>&nbsp;Editar</a>&nbsp;<a href='' class='btn btn-danger btn-xs' role=\"button\" data-toggle=\"modal\" data-target=\"#del" . md5($server->zone) . "\" aria-expanded=\"false\" aria-controls=\"del" . md5($server->zone) . "\"><i class='fa fa-times'></i>&nbsp;Excluir</a></td></tr>";
                            }
                        } else {
                            $output .= "<tr><td colspan='4' class='text-center'>Não foi encontrada nenhuma zona para este IP!</td></tr>";
                        }
                        $output .= "</tbody></table></div>";
                        foreach ($serverscurrent as $server) {
                            $output .= "<div class='modal fade' id='" . md5($server->zone) . "' tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"" . md5($server->zone) . "Label\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\" id=\"" . md5($server->zone) . "Label\">Editar Zona</h4></div><form method='get' action='addonmodules.php' class='form-horizontal'><input type='hidden' name='module' value='teamspeak'/><input type='hidden' name='display' value='tsdns'/><input type='hidden' name='serverip' value='{$_GET['serverip']}'/><input type='hidden' name='action' value='editzone'/><input type='hidden' name='oldzone' value='{$server->zone}'/><div class=\"modal-body\"><div class='form-group'><label for='zone' class='col-sm-2 control-label'>Zona</label><div class='col-sm-5'><input type='text' class='form-control' name='zone' id='zone' value='{$server->zone}'/></div></div><div class='form-group'><label for='target' class='col-sm-2 control-label'>Destino</label><div class='col-sm-5'><input type='text' class='form-control' name='target' id='target' value='{$server->target}'/></div></div></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\"><i class='fa fa-times'></i>&nbsp;Fechar</button><button type='submit' class='btn btn-success'><i class='fa fa-floppy-o'></i>&nbsp;Salvar</button></div></form></div></div></div>";
                        }
                        foreach ($serverscurrent as $server) {
                            $output .= "<div class='modal fade' id='del" . md5($server->zone) . "' tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"del" . md5($server->zone) . "Label\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\" id=\"del" . md5($server->zone) . "Label\">Confirmação de exclusão de zona</h4></div><form method='get' action='addonmodules.php' class='form-horizontal'><input type='hidden' name='module' value='teamspeak'/><input type='hidden' name='display' value='tsdns'/><input type='hidden' name='serverip' value='{$_GET['serverip']}'/><input type='hidden' name='action' value='delzone'/><input type='hidden' name='zone' value='{$server->zone}'/><div class=\"modal-body\"><h2>Se realmente desejar excluir a zona \"<strong><u>{$server->zone}</u></strong>\" clique em \"Excluir\". Deseja excluir está zona?</h2></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\"><i class='fa fa-times'></i>&nbsp;Fechar</button><button type='submit' class='btn btn-danger'><i class='fa fa-trash'></i>&nbsp;Excluir</button></div></form></div></div></div>";
                        }
                    }
                } catch (Exception $e) {
                    $alert['status'] = 'error';
                    $alert['alert'] .= $e->getMessage();
                }
                if (!empty($alert)) {
                    $output .= "<div class='row'><div class='col-md-6 col-md-offset-3'>";
                    if ($alert['status'] == 'error') {
                        $output .= "<div class='alert alert-danger'><a href='addonmodules.php?module=teamspeak' class='btn btn-danger pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Lista</a><p><strong><i class='fa fa-frown-o fa-4x pull-left'></i>Oops! Algo ocorreu!</strong></p>{$alert['alert']}</div>";
                    } else {
                        $output .= "<div class='alert alert-success'><a href='addonmodules.php?module=teamspeak&amp;display=tsdns&amp;serverip={$_GET['serverip']}' class='btn btn-success pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar para Lista</a><p><strong><i class='fa fa-smile-o fa-4x pull-left'></i>Parabéns! Deu tudo certo!</strong></p>{$alert['alert']}</div>";
                    }
                    $output .= "</div></div>";
                }
                break;
        }
    }
    $output .= " </div ></div ></div > ";
    echo $output;
}