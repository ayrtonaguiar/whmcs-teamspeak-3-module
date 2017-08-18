<a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn btn-primary pull-right"><i
            class="fa fa-arrow-left"></i>&nbsp;&nbsp;Voltar</a>
<div class="clearfix"></div>
<hr>
<div class="panel panel-default">
    <div class="panel-heading">Proibições</div>
    <div class="panel-body">
        <form method="get" action="clientarea.php">
            <input type="hidden" name="action" value="productdetails"/>
            <input type="hidden" name="id" value="{$serviceid}"/>
            <input type="hidden" name="modop" value="custom"/>
            <input type="hidden" name="a" value="bans"/>
            <input type="hidden" name="custom" value="delete"/>
            <div class="table-responsive">
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>IP/Nome/UID</th>
                        <th>Data/Hora</th>
                        <th>Motivo</th>
                        <th>Duração</th>
                        <th>Banido por</th>
                        <th>Opções</th>
                    </tr>
                    </thead>
                    <tbody>
                    {if !empty($bans)}
                        {assign var=var value=1}
                        {foreach $bans as $ban}
                            <tr>
                                <td>{$var}</td>
                                <td>{if $ban.ip}{$ban.ip|replace:"\\":""}{elseif $ban.name} {$ban.name}{elseif $ban.uid} {$ban.uid}{/if}</td>
                                <td>{$ban.created|date_format:"%d-%m-%Y %H:%M:%S"}</td>
                                <td>{$ban.reason}</td>
                                {assign var="sumban" value="`$ban.created+$ban.duration`"}
                                <td>{if $ban.duration eq 0}Indeterminada{else}{$sumban|date_format:"%d-%m-%Y %H:%M:%S"}{/if}</td>
                                <td>{$ban.invokername}</td>
                                <td>
                                    <button type="submit" class="btn btn-danger btn-xs" name="banid" id="{$ban.banid}"
                                            value="{$ban.banid}">Excluir
                                    </button>
                                </td>
                            </tr>
                            {capture assign=var}{$var+1}{/capture}
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="7" class="text-center">Não existe nenhum token</td>
                        </tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </form>
        <hr>
        <form method="get" action="clientarea.php" class="form-horizontal text-center">
            <input type="hidden" name="action" value="productdetails"/>
            <input type="hidden" name="id" value="{$serviceid}"/>
            <input type="hidden" name="modop" value="custom"/>
            <input type="hidden" name="a" value="bans"/>
            <div class="form-group">
                <label for="bantype" class="control-label col-sm-2">Selecione o Tipo:</label>
                <div class="col-sm-2">
                    <select name="bantype" id="bantype" class="form-control">
                        <option value="ip">IP</option>
                        <option value="name">Nome</option>
                        <option value="uid">UID</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="inu" class="control-label col-sm-2">IP/Nome/UID</label>
                <div class="col-sm-5">
                    <input type="text" class="form-control" name="inu" id="inu" value="" required/>
                </div>
            </div>
            <div class="form-group">
                <label for="reason" class="control-label col-sm-2">Motivo</label>
                <div class="col-sm-5">
                    <div class="input-group">
                        <input type="text" class="form-control" name="reason" id="reason" value=""/>
                <span class="input-group-btn">
                <button type="submit" class="btn btn-warning" name="custom" value="create"><i class="fa fa-ban"></i>&nbsp;&nbsp;Criar
                </button>
                    </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>