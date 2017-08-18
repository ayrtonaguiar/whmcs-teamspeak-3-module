{if $systemStatus == 'Active' && $settings.enabletsdns eq 1}
    <a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn btn-primary pull-right"><i
                class="fa fa-arrow-left"></i>&nbsp;&nbsp;Voltar</a>
    <div class="clearfix"></div>
    <hr>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Editar Subdomínio</h3>
        </div>
        <div class="panel-body">
            <form method="get" action="clientarea.php">
                <input type="hidden" name="action" value="productdetails"/>
                <input type="hidden" name="id" value="{$serviceid}"/>
                <input type="hidden" name="modop" value="custom"/>
                <input type="hidden" name="a" value="tsdns"/>
                <input type="hidden" name="oldzone" value="{$customfields.Subdominio}">
                <div class="row">
                    <div class="col-sm-9">
                        <div class="input-group">
                            <input type="text" name="zone" class="form-control" value="{$customfields.Subdominio}">
                            <span class="input-group-addon">.{$settings.domaintsdns}</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" name="ma" value="editzone" class="btn btn-primary btn-block"/>
                        <i class="fa fa-pencil-square-o"></i>
                        Editar
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
{else}
    <div class="row">
        <div class="col-md-offset-3 col-xs-offset-1 col-md-6 col-xs-10">
            <div class="alert alert-warning"><a href='clientarea.php?action=productdetails&id={$serviceid}'
                                                class='btn btn-warning pull-right'><i class='fa fa-arrow-left'></i>&nbsp;&nbsp;Voltar</a>
                <p><strong><i class='fa fa-frown-o fa-3x pull-left'></i>Oops! Algo ocorreu!</strong></p>Está opção está
                desabilitada
            </div>
        </div>
    </div>
{/if}