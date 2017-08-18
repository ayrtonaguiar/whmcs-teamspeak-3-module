<a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn btn-primary pull-right"><i
            class="fa fa-arrow-left"></i>&nbsp;&nbsp;Voltar</a>
<div class="clearfix"></div>
<hr>
<div class="panel panel-default">
    <div class="panel-heading">Privilégios</div>
    <div class="panel-body">
        <form method="get" action="clientarea.php">
            <input type="hidden" name="action" value="productdetails"/>
            <input type="hidden" name="id" value="{$serviceid}"/>
            <input type="hidden" name="modop" value="custom"/>
            <input type="hidden" name="a" value="tokens"/>
            <input type="hidden" name="custom" value="delete"/>
            <div class="table-responsive">
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome do Grupo</th>
                        <th>Data/Hora</th>
                        <th>Chave</th>
                        <th>Opções</th>
                    </tr>
                    </thead>
                    <tbody>
                    {if !empty($tokens)}
                        {assign var=var value=1}
                        {foreach $tokens as $token}
                            <tr>
                                <td>{$var}</td>
                                {foreach $sglist as $sg}
                                    {if $sg.sgid eq $token.token_id1}
                                        <td>{$sg.name}</td>
                                    {/if}
                                {/foreach}
                                <td>{$token.token_created|date_format:"%d-%m-%Y %H:%M:%S"}</td>
                                <td>{$token.token}</td>
                                <td>
                                    <button type="submit" class="btn btn-danger btn-xs" name="token" id="{$token.token}"
                                            value="{$token.token}">Excluir
                                    </button>
                                </td>
                            </tr>
                            {capture assign=var}{$var+1}{/capture}
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="2" class="text-center">Não existe nenhum token</td>
                        </tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </form>
        <hr>
        <form method="get" action="clientarea.php" class="form-inline text-center">
            <input type="hidden" name="action" value="productdetails"/>
            <input type="hidden" name="id" value="{$serviceid}"/>
            <input type="hidden" name="modop" value="custom"/>
            <input type="hidden" name="a" value="tokens"/>
            <div class="form-group">
                <label for="groupid">Selecione o grupo:</label>
                <select name="groupid" id="groupid" class="form-control">
                    {foreach $sglist as $sg}
                        <option value="{$sg.sgid}">{$sg.name}</option>
                    {/foreach}
                </select>
                <div class="form-group">
                    <label class="sr-only" for="desc">Descrição</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="desc" id="desc" value=""/>
                <span class="input-group-btn">
                <button type="submit" class="btn btn-success" name="custom" value="create"><i class="fa fa-key"></i>&nbsp;&nbsp;Criar
                </button>
                    </span>
                    </div>
                </div>
        </form>
    </div>
</div>