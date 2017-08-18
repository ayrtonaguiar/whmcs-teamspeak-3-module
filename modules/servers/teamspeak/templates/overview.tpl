<div class="row">

    <div class="col-md-6">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Pacote/Domínio</h3>
            </div>
            <div class="panel-body text-center">

                <em>{$groupname}</em>
                <h4 style="margin:0;">{$product}</h4>
                {if $settings.enabletsdns eq 1}
                    {$customfields.Subdominio}.{$settings.domaintsdns}
                    <p>
                        <a href="ts3server://{$customfields.Subdominio}.{$settings.domaintsdns}/?nickname={$clientsdetails.firstname}"
                           class="btn btn-info btn-sm" target="_top">Entrar no TeamSpeak</a></p>
                {else}
                    {$serverdata.ipaddress}:{$customfields.Porta}
                    <p>
                        <a href="ts3server://{$serverdata.ipaddress}:{$customfields.Porta}/?nickname={$clientsdetails.firstname}"
                           class="btn btn-info btn-sm" target="_top">Entrar no TeamSpeak</a></p>
                {/if}

            </div>
        </div>
    </div>

    <div class="col-md-6">
        {if $hostteamspeak.status eq true}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{$LANG.cPanel.quickShortcuts}
                        {if $hostteamspeak.vs.status eq true}
                            <span class="text-success pull-right"><strong>Servidor
                                    está Online</strong></span>
                        {else}
                            <span class="text-danger pull-right"><strong>Servidor
                                    está Offline</strong></span>
                        {/if}
                    </h3>
                </div>
                <div class="panel-body text-center">

                    {if $hostteamspeak.vs.status eq false}
                        <a href="clientarea.php?action=productdetails&amp;id={$serviceid}&amp;modop=custom&amp;a=start_server"
                           class="btn btn-success">
                            <i class="fa fa-play"></i> Iniciar
                        </a>
                    {else}
                        <a href="clientarea.php?action=productdetails&amp;id={$serviceid}&amp;modop=custom&amp;a=stop_server"
                           class="btn btn-danger">
                            <i class="fa fa-stop"></i> Parar
                        </a>
                    {/if}
                    <a href="clientarea.php?action=productdetails&amp;id={$serviceid}&amp;modop=custom&amp;a=reinstall_server"
                       class="btn btn-warning">
                        <i class="fa fa-repeat"></i> Reinstalar
                    </a>
                    <a href="clientarea.php?action=productdetails&amp;id={$serviceid}&amp;modop=custom&amp;a=perm_reset"
                       class="btn btn-primary">
                        <i class="fa fa-undo"></i> Rest. Permissões
                    </a>

                </div>
            </div>
        {elseif $hostteamspeak.status eq false}
            <div class="alert alert-danger text-center">Servidor está offline</div>
        {else}
            <div class="alert alert-warning text-center" role="alert">
                {if $suspendreason}
                    <strong>{$suspendreason}</strong>
                    <br/>
                {/if}
                {$LANG.cPanel.packageNotActive} {$status}.<br/>
                {if $systemStatus eq "Pending"}
                    {$LANG.cPanel.statusPendingNotice}
                {elseif $systemStatus eq "Suspended"}
                    {$LANG.cPanel.statusSuspendedNotice}
                {/if}
            </div>
        {/if}
    </div>
    {if $systemStatus == 'Active' && $hostteamspeak.status eq true}
        <div class="col-md-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Log</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                        <tr>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Descrição</th>
                        </tr>
                        <tbody>
                        {foreach $logs as $log}
                            <tr>
                                <td>{$log.0}</td>
                                <td>{$log.1}</td>
                                <td>{$log.4|substr:0:80}...</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    {/if}
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{$LANG.cPanel.billingOverview}</h3>
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-5">
                {if $firstpaymentamount neq $recurringamount}
                    <div class="row" id="firstPaymentAmount">
                        <div class="col-xs-6 text-right">
                            {$LANG.firstpaymentamount}
                        </div>
                        <div class="col-xs-6">
                            {$firstpaymentamount}
                        </div>
                    </div>
                {/if}
                {if $billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderfree}
                    <div class="row" id="recurringAmount">
                        <div class="col-xs-6 text-right">
                            {$LANG.recurringamount}
                        </div>
                        <div class="col-xs-6">
                            {$recurringamount}
                        </div>
                    </div>
                {/if}
                <div class="row" id="billingCycle">
                    <div class="col-xs-6 text-right">
                        {$LANG.orderbillingcycle}
                    </div>
                    <div class="col-xs-6">
                        {$billingcycle}
                    </div>
                </div>
                <div class="row" id="paymentMethod">
                    <div class="col-xs-6 text-right">
                        {$LANG.orderpaymentmethod}
                    </div>
                    <div class="col-xs-6">
                        {$paymentmethod}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row" id="registrationDate">
                    <div class="col-xs-6 col-md-5 text-right">
                        {$LANG.clientareahostingregdate}
                    </div>
                    <div class="col-xs-6 col-md-7">
                        {$regdate}
                    </div>
                </div>
                <div class="row" id="nextDueDate">
                    <div class="col-xs-6 col-md-5 text-right">
                        {$LANG.clientareahostingnextduedate}
                    </div>
                    <div class="col-xs-6 col-md-7">
                        {$nextduedate}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        {if $configurableoptions}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{$LANG.orderconfigpackage}</h3>
                </div>
                <div class="panel-body">
                    {foreach from=$configurableoptions item=configoption}
                        <div class="row">
                            <div class="col-md-5 col-xs-6 text-right">
                                <strong>{$configoption.optionname}</strong>
                            </div>
                            <div class="col-md-7 col-xs-6 text-left">
                                {if $configoption.optiontype eq 3}{if $configoption.selectedqty}{$LANG.yes}{else}{$LANG.no}{/if}{elseif $configoption.optiontype eq 4}{$configoption.selectedqty} x {$configoption.selectedoption}{else}{$configoption.selectedoption}{/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    </div>
    <div class="col-md-8">
        {if $customfields}
            <div class="panel panel-default" id="cPanelAdditionalInfoPanel">
                <div class="panel-heading">
                    <h3 class="panel-title">{$LANG.additionalInfo}</h3>
                </div>
                <div class="panel-body">
                    {foreach from=$customfields key=key item=field}
                        {if $key neq "Subdominio"}
                        <div class="row">
                            <div class="col-md-3 col-xs-3 text-right">
                                <strong>{$key}</strong>
                            </div>
                            <div class="col-md-9 col-xs-9 text-left">
                                {if empty($field)}
                                    {$LANG.blankCustomField}
                                {else}
                                    {$field}
                                {/if}
                            </div>
                        </div>
                        {/if}
                    {/foreach}
                </div>
            </div>
        {/if}
    </div>
</div>