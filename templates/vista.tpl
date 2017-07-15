<?php
/**
 * View of '${table_name}'.
 * @author: Nelson Ariza
 * @date: ${date}
 */
include ("header.php");
echo $this->alerta;
?>
<form id="formulario" method="post" class="form-horizontal" action="${controlador}.php" autocomplete="off">
    <div class="row">
        <div class="col-xs-12">
            <table border="1" class="table table-hover">
                <thead>
                    <tr>${encabezado_tabla}
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($this->filas as $fila) { 
                    ?>
                    <tr>${cuerpo_tabla}
                    </tr>
                    <?php
                    } 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="pull-right">
                <button type="submit" class="btn btn-success" name="accion" value='ingresar' >
                    <span class="glyphicon glyphicon-plus"></span>
                    Registrar Nuevo ${singular_class_name}
                </button>
            </div>
        </div>
    </div>
    <br>
    <hr>
    <!------------INICIO-MODAL-------------->
    <div class="modal fade" id="guardar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">
                        ${singular_class_name}
                    </h4>
                </div>
                <div class="modal-body">${cuerpo_modal}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="validateButton" name="accion" value="guardar">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!------------FIN-MODAL-------------->
</form>
<!------------INICIO-VALIDACIONES-------------->
<script type="text/javascript">
    $().ready(function () {
        $('#formulario').formValidation({
            message: 'Este valor no es correcto',
            framework: 'bootstrap',
            button: {
                selector: '#validateButton',
                disabled: 'disabled'
            },
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {${validaciones}
            }
        });
    });
</script>
<!------------FIN-VALIDACIONES-------------->
<?php
echo $this->modal;
include ("footer.php");