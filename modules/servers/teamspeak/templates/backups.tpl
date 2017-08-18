<a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn btn-primary pull-right"><i
            class="fa fa-arrow-left"></i>&nbsp;&nbsp;Voltar</a>
<div class="clearfix"></div>
<hr>
<div class="panel panel-default">
    <div class="panel-heading">Backups</div>
    <div class="panel-body">
        <form method="get" action="clientarea.php">
            <input type="hidden" name="action" value="productdetails"/>
            <input type="hidden" name="id" value="{$serviceid}"/>
            <input type="hidden" name="modop" value="custom"/>
            <input type="hidden" name="a" value="backups"/>

            <table class="table table-condensed">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Data/Hora</th>
                    <th><i class="fa fa-hand-o-down"></i></th>
                </tr>
                </thead>
                <tbody>
                {if !empty($backups)}
                    {assign var=var value=1}
                    {foreach $backups as $backup}
                        <tr>
                            <td>{$var}</td>
                            <td>{$backup.date|date_format:"%d/%m/%Y %H:%M:%S"}</td>
                            <td><input type="radio" name="backupid" id="{$backup.id}" value="{$backup.id}"
                                       {if $var eq 1}checked{/if}/></td>
                        </tr>
                        {capture assign=var}{$var+1}{/capture}
                    {/foreach}
                {else}
                    <tr>
                        <td colspan="2" class="text-center">Não existe nenhum backup</td>
                    </tr>
                {/if}
                </tbody>
            </table>
            </br>
            <div class="btn-group pull-right">
                <button type="submit" class="btn btn-success" name="custom" value="create"><i class="fa fa-hdd-o"></i>&nbsp;&nbsp;Criar
                </button>
                <button type="submit" class="btn btn-primary" name="custom" value="download"><i
                            class="fa fa-download"></i>&nbsp;&nbsp;Baixar
                </button>
                <button type="submit" class="btn btn-warning" name="custom" value="restore"><i class="fa fa-undo"></i>&nbsp;&nbsp;Restatuar
                </button>
                <button type="submit" class="btn btn-danger" name="custom" value="delete"><i class="fa fa-trash" aria-hidden="true"></i>&nbsp;&nbsp;Excluir
                </button>
            </div>

        </form>
    </div>
</div>