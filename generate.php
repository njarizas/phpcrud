<?php

require_once ('templates/class/Connection.class.php');
require_once ('templates/class/ConnectionFactory.class.php');
require_once ('templates/class/ConnectionFactoryMySqlPDO.class.php');
require_once ('templates/class/QueryExecutor.class.php');
require_once ('templates/class/SqlQuery.class.php');
require_once ('templates/class/Transaction.class.php');
require_once ('templates/class/Template.class.php');
require_once ('templates/class/Config.class.php');

function generate() {
    ini_set('max_execution_time', 0);
    init();
    $sql = 'SHOW TABLES';
    //$ret = QueryExecutor::execute(new SqlQuery($sql));
    $ret = QueryExecutor::executePDO($sql);
    generateDTOObjects($ret);//ya
    generateDAOObjects($ret);//ya
    generateDAOExtObjects($ret);//ya
    generateViewFiles($ret);
    generateControllerFiles($ret);
    generateDispatchers($ret);
    createMenuFile($ret);
    createIncludeFile($ret);
    createDAOFactory($ret);
    generateHeader();
}

function init() {
    $config = Config::getInstance();
    @mkdir("generated");
    @mkdir("generated/class");
    @mkdir("generated/class/".$config->get('modelsFolder'));
    @mkdir("generated/class/".$config->get('modelsFolder')."/dto");
    @mkdir("generated/class/".$config->get('modelsFolder')."/dao");
    @mkdir("generated/class/".$config->get('modelsFolder')."/dao/ext");
    @mkdir("generated/class/".$config->get('viewsFolder'));
    @mkdir("generated/class/".$config->get('controllersFolder'));
    @mkdir("generated/class/".$config->get('configFolder'));
    copy('templates/class/ConnectionFactoryMySqlPDO.class.php', 'generated/class/'.$config->get('configFolder').'/ConnectionFactoryMySqlPDO.class.php');
    copy('templates/class/DAO.class.php', 'generated/class/'.$config->get('modelsFolder').'/dao/DAO.class.php');
    copy('templates/class/Config.class.php', 'generated/class/'.$config->get('configFolder').'/Config.class.php');
    copy('templates/class/config.php', 'generated/class/'.$config->get('configFolder').'/config.php');
    copiar('templates/view', 'generated/class/'.$config->get('viewsFolder'));
}

function createIncludeFile($ret) {
    $config = Config::getInstance();
    $configFolder = $config->get('configFolder');
    $modelFolder = $config->get('modelsFolder');
    $str = "\n";
    for ($i = 0;$i < count($ret);$i++) {
        $tableName = $ret[$i][0];
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $pluralClassName = getClassName($tableName);
        $singularClassName = getDTOName($tableName);
        $str.= "\trequire_once('class/".$modelFolder."/dto/" . $singularClassName . ".class.php');\n";
        $str.= "\trequire_once('class/".$modelFolder."/dao/" . $pluralClassName . "DAO.class.php');\n";
        $str.= "\trequire_once('class/".$modelFolder."/dao/ext/" . $pluralClassName . "ExtDAO.class.php');\n";
    }
    $template = new Template('templates/include.tpl');
    $template->set('config_folder', $configFolder);
    $template->set('model_folder', $modelFolder);
    $template->set('include', $str);
    $template->write('generated/include.php');
}

function doesTableContainPK($row) {
    $row = getFields($row[0]);
    for ($j = 0;$j < count($row);$j++) {
        if ($row[$j][3] == 'PRI') {
            return true;
        }
    }
    return false;
}

function doesTableContainAutoIncrement($row) {
    $row = getFields($row[0]);
    for ($j = 0;$j < count($row);$j++) {
        if ($row[$j][5] == 'auto_increment') {
            return true;
        }
    }
    return false;
}

/**
 * Metodo que genera el DAOFactory
 */
function createDAOFactory($ret) {
    $config = Config::getInstance();
    $str = "\n";
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $str.= "\t/**\n";
        $str.= "\t * @return " . $pluralClassName . "DAO\n";
        $str.= "\t */\n";
        $str.= "\tpublic static function get" . $pluralClassName . "DAO(){\n";
        $str.= "\t\treturn new " . $pluralClassName . "ExtDAO();\n";
        $str.= "\t}\n\n";
    }
    $template = new Template('templates/DAOFactory.tpl');
    $template->set('date', date("Y-m-d H:i"));
    $template->set('content', $str);
    $template->write('generated/class/'.$config->get('modelsFolder').'/dao/DAOFactory.class.php');
}

/**
 * Metodo que genera el Menu
 */
function createMenuFile($ret) {
    $config = Config::getInstance();
    $str = "\n";
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        if ($i%10==0){
            if ($i!=0){
                $str .="</ul>\n\t\t\t\t</li>\n";
            }
            $str .="\t\t\t\t<li class=\"dropdown\">\n\t\t\t\t\t<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\">Grupo ".(($i/10)+1)."<span class=\"caret\"></span></a>\n\t\t\t\t\t<ul class=\"dropdown-menu\">\n\t\t\t\t\t";
        }
        $str.="\t<li><a href=\"". strtolower(str_replace("_", "-", $tableName)) . ".php\">". $pluralClassName . "</a></li>\n\t\t\t\t\t";
    }
    $str .="</ul>\n\t\t\t\t</li>\n\t\t\t";
    $template = new Template('templates/menu.tpl');
    $template->set('menu', $str);
    $template->write('generated/class/'.$config->get('viewsFolder').'/menu.php');
}

/**
 * Metodo que genera los objetos DTO
 */
function generateDTOObjects($ret) {
    $config = Config::getInstance();
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $singularClassName = getDTOName($tableName);
        $template = new Template('templates/DTO.tpl');
        $tab = getFields($tableName);
        $variables = "";
        $constructor = "";
        $getters = "";
        $setters = "";
        //variables
        for ($j = 0;$j < count($tab);$j++) {
            $variables.= "\tprivate $" . getVarNameWithS($tab[$j][0]) . ";\n";
        }
        //constructor
        $camposObligatorios = array();
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][2] == 'NO' && $tab[$j][5] != 'auto_increment') {
                $camposObligatorios[] = $tab[$j];
            }
        }
        $constructor.= "\tpublic function __construct(";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            if ($j != 0) {
                $constructor.= ",";
            }
            $constructor.= "$" . getVarNameWithS($camposObligatorios[$j][0]);
        }
        $constructor.= ") {\n";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            $constructor.= "\t\t$" . "this->" . getVarNameWithS($camposObligatorios[$j][0]) . " = $" . getVarNameWithS($camposObligatorios[$j][0]) . ";\n";
        }
        $constructor.= "\t}\n";
        //getters
        for ($j = 0;$j < count($tab);$j++) {
            $getters.= "\tpublic function get" . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "() {\n\t\treturn $" . "this->" . getVarNameWithS($tab[$j][0]) . ";\n\t}\n\r";
        }
        //setters
        for ($j = 0;$j < count($tab);$j++) {
            $setters.= "\tpublic function set" . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "($" . getVarNameWithS($tab[$j][0]) . ") {\n\t\t$" . "this->" . getVarNameWithS($tab[$j][0]) . " = $" . getVarNameWithS($tab[$j][0]) . ";\n\t}\n\r";
        }
        $template->set('table_name', $tableName);
        $template->set('date', date("Y-m-d H:i"));
        $template->set('singular_class_name', $singularClassName);
        $template->set('variables', $variables);
        $template->set('constructor', $constructor);
        $template->set('getters', $getters);
        $template->set('setters', $setters);
        $template->write('generated/class/'.$config->get('modelsFolder').'/dto/' . $singularClassName . '.class.php');
    }
}

/**
 * Metodo que genera los DAO Extendidos para poner consultas personalizadas
 */
function generateDAOExtObjects($ret) {
    $config = Config::getInstance();
    //recorre las tablas
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $tab = getFields($tableName);
        $pk = '';
        $queryByField = '';
        $deleteByField = '';
        //recorre las columnas
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][3] == 'PRI') {
                $pk = $tab[$j][0];
            } else {
                $queryByField.= "	public function buscar" . $pluralClassName . "Por" . getClassName($tab[$j][0]) . "(\$" . $tab[$j][0] . "){
		\$sql = 'SELECT * FROM " . $tableName . " WHERE " . $tab[$j][0] . " = ?';
        \$var1=\$" . $tab[$j][0] . ";
        \$stmt = \$this->conn->prepare(\$sql);
        \$stmt->bindparam(1, \$var1);
        \$stmt->execute();
        return \$stmt->fetchAll();
	}\n\n";
                $deleteByField.= "	public function eliminar" . $pluralClassName . "Por" . getClassName($tab[$j][0]) . "(\$" . $tab[$j][0] . "){
		\$sql = 'DELETE FROM " . $tableName . " WHERE " . $tab[$j][0] . " = ?';
        \$var1=\$" . $tab[$j][0] . ";
		\$stmt = \$this->conn->prepare(\$sql);
        \$stmt->bindparam(1, \$var1);
        \$cantidadEliminada=\$stmt->execute();
        return \$cantidadEliminada;
	}\n\n";
            }
        }
        if ($pk == '') {
            continue;
        }
        $template = new Template('templates/DAOExt.tpl');
        $template->set('date', date("Y-m-d H:i"));
        $template->set('plural_class_name', $pluralClassName);
        $template->set('table_name', $tableName);
        $template->set('queryByFieldFunctions', $queryByField);
        $template->set('deleteByFieldFunctions', $deleteByField);
        $file = 'generated/class/'.$config->get('modelsFolder').'/dao/ext/' . $pluralClassName . 'ExtDAO.class.php';
        if (!file_exists($file)) {
            $template->write('generated/class/'.$config->get('modelsFolder').'/dao/ext/' . $pluralClassName . 'ExtDAO.class.php');
        }
    }
}

/**
 * Metodo que genera los objetos DAO
 */
function generateDAOObjects($ret) {
    $config = Config::getInstance();
    //recorre las tablas
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $tab = getFields($tableName);
        $prepareParameters = "";
        $prepareParametersWithPks = "";
        $prepareWhere = "";
        $prepareWhereInsert = "";
        $prepareWhere2 = "";
        $parameterSetter = "";
        $parameterSetterWithPks = "";
        $whereSetter = "";
        $whereSetterInsert = "";
        $autogeneratedId = "";
        $insertFields = "";
        $updateFields = "";
        $questionMarks = "";
        $pk = '';
        $pks = array();
        $pk_type = '';
        $param = 0;
        $param2 = 0;
        //recorre las columnas
        for ($j = 0;$j < count($tab);$j++) {
            if (doesTableContainAutoIncrement($ret[$i])) {
                if ($tab[$j][5] == 'auto_increment') {
                    $autogeneratedId.= "\n\t\t\t" . '$idGenerado=$this->conn->lastInsertId();' . "\n\t\t\t" . '$' . getVarName($tableName) . '->set' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . '($idGenerado);';
                }
            }
            if ($tab[$j][3] == 'PRI') {
                $pk = $tab[$j][0];
                $c = count($pks);
                $pks[$c] = $tab[$j][0];
                $pk_type = $tab[$j][1];
                $updateFields.= $tab[$j][0] . " = " . $tab[$j][0] . ", ";
                if ($tab[$j][5] != 'auto_increment') {
                    $param2++;
                    $insertFields.= $tab[$j][0] . ", ";
                    $questionMarks.= "?, ";
                    $prepareParametersWithPks.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                    $parameterSetterWithPks.= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
                }
            } else {
                $param++;
                $param2++;
                $insertFields.= $tab[$j][0] . ", ";
                $updateFields.= $tab[$j][0] . " = ?, ";
                $questionMarks.= "?, ";
                $prepareParameters.= "\n\t\t\$var" . $param . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                $prepareParametersWithPks.= "\n\t\t\t\$var" . $param2 . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($tab[$j][0]) [0]) . substr(getVarNameWithS($tab[$j][0]), 1, strlen(getVarNameWithS($tab[$j][0]))) . "();";
                $parameterSetter.= "\n\t\t\$stmt->bindparam(" . $param . ', $var' . $param . ");";
                $parameterSetterWithPks.= "\n\t\t\t\$stmt->bindparam(" . $param2 . ', $var' . $param2 . ");";
            }
        }
        if ($pk == '') {
            continue;
        }
        if (count($pks) == 1) {
            $template = new Template('templates/DAO.tpl');
        } else {
            $template = new Template('templates/DAO_with_complex_pk.tpl');
        }
        $insertFields = substr($insertFields, 0, strlen($insertFields) - 2);
        $updateFields = substr($updateFields, 0, strlen($updateFields) - 2);
        $questionMarks = substr($questionMarks, 0, strlen($questionMarks) - 2);
        $s = '';
        $pkWhere = '';
        $insertFields2 = $insertFields;
        $questionMarks2 = $questionMarks;
        for ($z = 0;$z < count($pks);$z++) {
            $param++;
            $prepareWhere.= "\n\t\t\$var" . $param . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($pks[$z]) [0]) . substr(getVarNameWithS($pks[$z]), 1, strlen(getVarNameWithS($pks[$z]))) . "();";
            $prepareWhereInsert.= "\n\t\t\t\$var" . $param . ' = $' . getVarName($tableName) . '->get' . strtoupper(getVarNameWithS($pks[$z]) [0]) . substr(getVarNameWithS($pks[$z]), 1, strlen(getVarNameWithS($pks[$z]))) . "();";
            $prepareWhere2 .= "\n\t\t\$var" . $param . ' = $' . getVarNameWithS($pks[$z]) . ";";
            $whereSetter .= "\n\t\t\$stmt->bindparam(" . $param . ', $var' . $param . ");";
            $whereSetterInsert .= "\n\t\t\t\$stmt->bindparam(" . $param . ', $var' . $param . ");";
            $questionMarks2.= ', ?';
            if ($z > 0) {
                $s.= ', ';
                $pkWhere.= ' AND ';
            }
            $insertFields2.= ', ' . $pks[$z];
            $s.= '$' . getVarNameWithS($pks[$z]);
            $pkWhere.= $pks[$z] . ' = ? ';
        }
        if ($s[0] == ',') {
            $s = substr($s, 1);
        }
        if ($questionMarks2[0] == ',') {
            $questionMarks2 = substr($questionMarks2, 1);
        }
        if ($insertFields2[0] == ',') {
            $insertFields2 = substr($insertFields2, 1);
        }
        $template->set('table_name', $tableName);
        $template->set('date', date("Y-m-d H:i"));
        $template->set('plural_class_name', $pluralClassName);
        $template->set('var_name', getVarName($tableName));
        $template->set('update_fields', $updateFields);
        $template->set('pk', $pk);
        $template->set('prepare_parameters', $prepareParameters);
        $template->set('prepare_where', $prepareWhere);
        $template->set('parameter_setter', $parameterSetter);
        $template->set('where_setter', $whereSetter);
        $template->set('insert_fields', $insertFields);
        $template->set('question_marks', $questionMarks);
        $template->set('prepare_parameters_with_pks', $prepareParametersWithPks);
        $template->set('parameter_setter_with_pks', $parameterSetterWithPks);
        $template->set('autogenerated_id', $autogeneratedId);
        
        $template->set('pk_where', $pkWhere);
        $template->set('pks', $s);
        $template->set('prepare_where2', $prepareWhere2);
        $template->set('prepare_where_insert', $prepareWhereInsert);
        $template->set('where_setter_insert', $whereSetterInsert);
        $template->set('insert_fields2', $insertFields2);
        $template->set('question_marks2', $questionMarks2);        
        $template->write('generated/class/'.$config->get('modelsFolder').'/dao/' . $pluralClassName . 'DAO.class.php');
    }
}

/**
 * Metodo que genera las vistas
 */
function generateViewFiles($ret) {
    $config = Config::getInstance();
    for ($i = 0;$i < count($ret);$i++) {
        $pk = '';
        $pks = array();
        $pk_type = '';
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $singularClassName = getDTOName($tableName);;
        $template = new Template('templates/vista.tpl');
        $tab = getFields($tableName);
        $encabezadoTabla = "";
        $cuerpoTabla = "";
        $validaciones = "";
        $cuerpoModal = "";
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][3] == 'PRI') {
                $pk = $tab[$j][0];
                $c = count($pks);
                $pks[$c] = $tab[$j][0];
                $pk_type = $tab[$j][1];
            }
            $encabezadoTabla.= "\n\t\t\t\t\t\t<th data-halign=\"right\" data-align=\"center\" data-sortable=\"true\" data-field=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\"><strong>" . $tab[$j][0] . "</strong></th>";
            $cuerpoTabla.= "\n\t\t\t\t\t\t<td><?= \$fila['" . $tab[$j][0] . "']; ?></td>";
            if ($tab[$j][3] == "MUL" || strtolower(substr($tab[$j][1], 0, 4)) == 'enum' || strtolower(substr($tab[$j][1], 0, 3)) == 'set') {
                if ($tab[$j][3] == "MUL") {
                    $tab2 = getForeignKey($tableName, $tab[$j][0]);
                    if (count($tab2) > 0) {
                        $cuerpoModal.= "\n\t\t\t\t\t<div class=\"form-group\">
                            <label class=\"col-sm-4  control-label\" for=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\">" . str_replace("_", " ", $tab[$j][0]) . ":</label>
                            <div class=\"col-sm-8\">
                                <select class=\"form-control\" id=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" name=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\">
                                    <option value=\"\" disabled selected>Seleccione...</option>
                                    <?php
                                    \$lista" . getClassName($tab2[0][2]) . " = DAOFactory::get" . getClassName($tab2[0][2]) . "DAO()->listar();
                                    foreach (\$lista" . getClassName($tab2[0][2]) . " as \$fila) {
                                        if (\$this->" . getVarNameWithS($tab[$j][0]) . "==\$fila['" . $tab2[0][3] . "']){
                                            echo \"<option value=\\\"\" . \$fila['" . $tab2[0][3] . "'] . \"\\\" selected>\" . \$fila['" . $tab2[0][3] . "'] .  \"</option>\";
                                        } else{
                                            echo \"<option value=\\\"\" . \$fila['" . $tab2[0][3] . "'] .\"\\\">\" . \$fila['" . $tab2[0][3] . "'] . \"</option>\";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>";
                    }
                } else {
                    $array = multiexplode(array("(",",",")"),$tab[$j][1]);
                    $cuerpoModal.= "\n\t\t\t\t\t<div class=\"form-group\">
                            <label class=\"col-sm-4  control-label\" for=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\">" . str_replace("_", " ", $tab[$j][0]) . ":</label>
                            <div class=\"col-sm-8\">
                                <select class=\"form-control\" id=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" name=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\">
                                    <option value=\"\" disabled selected>Seleccione...</option>";
                    for($k=1;$k<count($array)-1;$k++){
                        $cuerpoModal .=  "\n\t\t\t\t\t\t\t\t\t<option value=" . $array[$k] . " <?php if(htmlspecialchars(\$this->".getVarNameWithS($tab[$j][0]).")==". $array[$k] ."){ echo \"selected\"; }?>>" . str_replace("'", "",$array[$k]) . "</option>\";";
                    }
                    $cuerpoModal .="
                                </select>
                            </div>
                        </div>";
                }
            } else if ($tab[$j][3] == "" || $tab[$j][3] == "UNI") {
                $cuerpoModal.= "\n\t\t\t\t\t<div class=\"form-group\">
                        <label class=\"col-sm-4 control-label\" for=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\">" . str_replace("_", " ", $tab[$j][0]) . ":</label>
                        <div class=\"col-sm-8\">
                            <input ";
                if (isColumnTypeNumber($tab[$j][1])) {
                    $cuerpoModal.= "type=\"number\" ";
                } else if ($tab[$j][1]=="date") {
                    $cuerpoModal.= "type=\"date\" ";
                } else {
                    $cuerpoModal.= "type=\"text\" ";
                }
                $cuerpoModal.= "class=\"form-control\" id=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" name=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" placeholder=\"Ej: \" value=\"<?= htmlspecialchars(\$this->" . getVarNameWithS($tab[$j][0]) . "); ?>\">
                        </div>
                    </div>";
            } else if (($tab[$j][3] == "PRI") && ($tab[$j][5] == "auto_increment")){
                 $cuerpoModal.= "\n\t\t\t\t\t<div class=\"form-group\">
                        <label class=\"col-sm-4 control-label\">" . str_replace("_", " ", $tab[$j][0]) . ":</label>
                        <label class=\"col-sm-8\"><?= htmlspecialchars(\$this->" . getVarNameWithS($tab[$j][0]) . "); ?></label>
                        <div class=\"col-sm-8\">
                            <input type=\"hidden\" class=\"form-control\" id=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" name=\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\" value=\"<?= htmlspecialchars(\$this->" . getVarNameWithS($tab[$j][0]) . "); ?>\">
                        </div>
                    </div>";
            }
            if($tab[$j][5] != "auto_increment") {
                $validaciones .= "\n\t\t\t\t".'\'' . strtolower(str_replace("_", "-", $tab[$j][0])) . '\': {
                    row: \'.col-sm-8\',
                    validators: {';
                if($tab[$j][2] == "NO") {
                    $validaciones .= '
                        notEmpty: {
                            message: \'El campo \\\''. str_replace("_", " ", $tab[$j][0]) . '\\\' es obligatorio\'
                        }';
                    if(isColumnTypeText($tab[$j][1])){
                        $maxLength = multiexplode(array("(",")"),$tab[$j][1])[1];
                        $validaciones .= ",\n\t\t\t\t\t\t".'stringLength: {
                            min: 0,
                            max: ' . $maxLength . ',
                            message: \'El campo \\\'' . str_replace("_", " ", $tab[$j][0]) . '\\\' debe contener menos de ' . $maxLength . ' caracteres\'
                        }';
                    }
                } else if ($tab[$j][2] == "YES") {
                    if(isColumnTypeText($tab[$j][1])){
                        if(isColumnTypeText($tab[$j][1])){
                            $maxLength = multiexplode(array("(",")"),$tab[$j][1])[1];
                            $validaciones .= "\n\t\t\t\t\t\t".'stringLength: {
                            min: 0,
                            max: ' . $maxLength . ',
                            message: \'El campo \\\'' . str_replace("_", " ", $tab[$j][0]) . '\\\' debe contener menos de ' . $maxLength . ' caracteres\'
                        }';
                        }
                    }
                }
                $validaciones .='
                    }
                },';
            }
        }
        if (strlen($validaciones)>5){
            if ($validaciones[strlen($validaciones)-1] == ',') {
                $validaciones = substr($validaciones, 0,strlen($validaciones)-1);
            }
        }
        if (count($pks) == 1) {
            $encabezadoTabla.= "\n\t\t\t\t\t\t<th><strong>Acciones_:</strong></th>";
            $cuerpoTabla.= "\n\t\t\t\t\t\t".'<td>
                            <button type="submit" class="btn btn-sm btn-warning" name="actualizar" value=\'<?= $fila[\'' . $pk . '\']; ?>\' >
                                <span class="glyphicon glyphicon-pencil"></span>
                            </button>
                            <button type="submit" class="btn btn-sm btn-danger" name="eliminar" value=\'<?= $fila[\'' . $pk . '\']; ?>\' >
                                <span class="glyphicon glyphicon-trash"></span>
                            </button>
                        </td>';
        }
        $template->set('table_name', $tableName);
        $template->set('id_pk', strtolower(str_replace("_", "-", $pk)));
        $template->set('date', date("Y-m-d H:i"));
        $template->set('controlador', strtolower(str_replace("_", "-", $tableName)));
        $template->set('encabezado_tabla', $encabezadoTabla);
        $template->set('cuerpo_tabla', $cuerpoTabla);
        $template->set('singular_class_name', $singularClassName);
        $template->set('cuerpo_modal', $cuerpoModal);
        $template->set('validaciones', $validaciones);
        $template->write('generated/class/' . $config->get('viewsFolder') . '/' . strtolower(str_replace("_", "-", $tableName)) . '-view.php');
    }
}

/**
 * Metodo que genera los controladores
 */
function generateControllerFiles($ret) {
    $config = Config::getInstance();
    $viewFolder = $config->get('viewsFolder');
    for ($i = 0;$i < count($ret);$i++) {
        $pk = '';
        $pks = array();
        $pk_type = '';
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $clazzName = getClassName($tableName);
        $domainName = getClassName($tableName);
        if ($domainName[strlen($domainName) - 1] == 's') {
            $domainName = substr($domainName, 0, strlen($domainName) - 1);
        }
        $nombreObjetoDto = "";
        $nombreObjetoDto = strtolower($domainName[0]) . substr($domainName, 1, strlen($domainName));
        $declaraVariables = "";
        $limpiarVariables = "";
        $asignarVariables = "";
        $obtenerParametros = "";
        $constructor = "";
        $asignarAtributos = "";
        $asignarPk = "";
        $funcionActualizar = "";
        $funcionInsertar = "";
        $funcionEliminar = "";
        $buscarObjeto = "";
        $template = new Template('templates/Controller.tpl');
        $tab = getFields($tableName);
        $viewFileName = strtolower(str_replace("_", "-", $tableName));
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][3] == 'PRI') {
                $pk = getVarNameWithS($tab[$j][0]);
                $c = count($pks);
                $pks[$c] = $tab[$j][0];
                $pk_type = $tab[$j][1];
            }
            $obtenerParametros.= "\$this->" . getVarNameWithS($tab[$j][0]) . " = \"\";\n\t\t\tif (isset(\$_POST[\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\"])){\n\t\t\t\t \$this->" . getVarNameWithS($tab[$j][0]) . " = (\$_POST[\"" . strtolower(str_replace("_", "-", $tab[$j][0])) . "\"]);\n\t\t\t}\n\t\t\t";
            $declaraVariables.= "public $" . getVarNameWithS($tab[$j][0]) . ";\n\t";
            $limpiarVariables.= "\$this->" . getVarNameWithS($tab[$j][0]) . " = \"\";\n\t\t";
            $asignarVariables.= "\$this->" . getVarNameWithS($tab[$j][0]) . " = $\${nombre_objeto_dto}['" . $tab[$j][0] . "'];\n\t\t";
        }
        $buscarObjeto.= "$\${nombre_objeto_dto} = DAOFactory::get" . $clazzName . "DAO()->buscar(\$this->${pk});";
        $funcionEliminar.= "DAOFactory::get" . $clazzName . "DAO()->eliminar(";
        for ($j = 0;$j < count($pks);$j++) {
            if ($j != 0) {
                $funcionEliminar.= ",";
            }
            $funcionEliminar.= "\$this->" . getVarNameWithS($pks[$j]);
            $asignarPk.= "$\${nombre_objeto_dto}->set" . strtoupper(getVarNameWithS($pks[$j][0])) . substr(getVarNameWithS($pks[$j]), 1, strlen(getVarNameWithS($pks[$j]))) . "(\$this->" . getVarNameWithS($pks[$j]) . ");\n\t\t\t\t";
        }
        $funcionEliminar.= ")";
        //constructor
        $camposObligatorios = array();
        $camposOpcionales = array();
        for ($j = 0;$j < count($tab);$j++) {
            if ($tab[$j][2] == 'NO' && $tab[$j][5] != 'auto_increment') {
                $camposObligatorios[] = $tab[$j];
            } else if ($tab[$j][2] == 'YES') {
                $camposOpcionales[] = $tab[$j];
            }
        }
        $constructor.= "$\${nombre_objeto_dto} = new \${domain_name}(";
        for ($j = 0;$j < count($camposObligatorios);$j++) {
            if ($j != 0) {
                $constructor.= ",";
            }
            $constructor.= "\$this->" . getVarNameWithS($camposObligatorios[$j][0]);
        }
        $constructor.= ");\n";
        //atributos opcionales
        for ($j = 0;$j < count($camposOpcionales);$j++) {
            $asignarAtributos.= "$\${nombre_objeto_dto}->set" . strtoupper(getVarNameWithS($camposOpcionales[$j][0]) [0]) . substr(getVarNameWithS($camposOpcionales[$j][0]), 1, strlen(getVarNameWithS($camposOpcionales[$j][0]))) . "(\$this->" . getVarNameWithS($camposOpcionales[$j][0]) . ");\n\t\t\t";
        }
        $funcionActualizar.= "DAOFactory::get" . $clazzName . "DAO()->actualizar($\${nombre_objeto_dto})";
        $funcionInsertar.= "DAOFactory::get" . $clazzName . "DAO()->insertar($\${nombre_objeto_dto})";
        $template->set('table_name', $tableName);
        $template->set('pk', $pk);
        $template->set('declara_variables', $declaraVariables);
        $template->set('limpiar_variables', $limpiarVariables);
        $template->set('asignar_variables', $asignarVariables);
        $template->set('asignar_pk', $asignarPk);
        $template->set('buscar_objeto', $buscarObjeto);
        $template->set('constructor', $constructor);
        $template->set('asignar_atributos', $asignarAtributos);
        $template->set('domain_name', $domainName);
        $template->set('funcion_actualizar', $funcionActualizar);
        $template->set('funcion_insertar', $funcionInsertar);
        $template->set('funcion_eliminar', $funcionEliminar);
        $template->set('nombre_objeto_dto', $nombreObjetoDto);
        $template->set('obtener_parametros', $obtenerParametros);
        $template->set('date', date("Y-m-d H:i"));
        $template->set('dao_clazz_name', $clazzName);
        $template->set('view_folder',$viewFolder);
        $template->set('view_file_name', $viewFileName);
        $template->write('generated/class/'.$config->get('controllersFolder').'/' . $clazzName . 'Controller.php');
    }
}

/**
 * Metodo que genera los dispatchers
 */
function generateDispatchers($ret) {
    $config = Config::getInstance();
    for ($i = 0;$i < count($ret);$i++) {
        if (!doesTableContainPK($ret[$i])) {
            continue;
        }
        $tableName = $ret[$i][0];
        $pluralClassName = getClassName($tableName);
        $controllerFolder=$config->get('controllersFolder');
        $template = new Template('templates/dispatch.tpl');
        $template->set('table_name', $tableName);
        $template->set('controller_folder', $controllerFolder);
        $template->set('plural_class_name', $pluralClassName);
        $template->set('date', date("Y-m-d H:i"));
        $template->write('generated/' . strtolower(str_replace("_", "-", $tableName)) . '.php');
    }
}

/**
 * Metodo que genera el header
 */
function generateHeader() {
    $config = Config::getInstance();
    $viewFolder=$config->get('viewsFolder');
    $template = new Template('templates/header.tpl');
    $template->set('view_folder', $viewFolder);
    $template->write('generated/class/'.$viewFolder.'/header.php');
}

function isColumnTypeNumber($columnType) {
    //echo $columnType.'<br/>';
    if (strtolower(substr($columnType, 0, 3)) == 'int' || strtolower(substr($columnType, 0, 7)) == 'tinyint'
        || strtolower(substr($columnType, 0, 8)) == 'smallint' || strtolower(substr($columnType, 0, 9)) == 'mediumint'
        || strtolower(substr($columnType, 0, 6)) == 'bigint') {
        return true;
    }
    return false;
}

function isColumnTypeText($columnType) {
    //echo $columnType.'<br/>';
    if (strtolower(substr($columnType, 0, 4)) == 'char' || strtolower(substr($columnType, 0, 7)) == 'varchar') {
        return true;
    }
    return false;
}

function getFields($table) {
    $sql = 'DESC ' . $table;
    //return QueryExecutor::execute(new SqlQuery($sql));
    return QueryExecutor::executePDO($sql);
}

function getForeignKey($table, $column) {
    $sql = 'SELECT table_name, column_name, referenced_table_name, referenced_column_name 
    FROM
    information_schema.key_column_usage
    WHERE
    referenced_table_name IS NOT NULL
    AND CONSTRAINT_SCHEMA = database()
    AND table_name = \'' . $table . '\'
    AND column_name = \'' . $column . '\'';
    //return QueryExecutor::execute(new SqlQuery($sql));
    return QueryExecutor::executePDO($sql);
}

function getClassName($tableName) {
    $tableName = strtoupper($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    return $tableName;
}

function getDTOName($tableName) {
    $name = getClassName($tableName);
    if ($name[strlen($name) - 1] == 's') {
        $name = substr($name, 0, strlen($name) - 1);
    }
    return $name;
}

function getVarName($tableName) {
    $tableName = strtolower($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    if ($tableName[strlen($tableName) - 1] == 's') {
        $tableName = substr($tableName, 0, strlen($tableName) - 1);
    }
    return $tableName;
}

function getVarNameWithS($tableName) {
    $tableName = strtolower($tableName[0]) . substr($tableName, 1);
    for ($i = 0;$i < strlen($tableName);$i++) {
        if ($tableName[$i] == '_') {
            $tableName = substr($tableName, 0, $i) . strtoupper($tableName[$i + 1]) . substr($tableName, $i + 2);
        }
    }
    return $tableName;
}

function copiar($fuente, $destino) {
    if (is_dir($fuente)) {
        $dir = opendir($fuente);
        while ($archivo = readdir($dir)) {
            if ($archivo != "." && $archivo != "..") {
                if (is_dir($fuente . "/" . $archivo)) {
                    if (!is_dir($destino . "/" . $archivo)) {
                        mkdir($destino . "/" . $archivo);
                    }
                    copiar($fuente . "/" . $archivo, $destino . "/" . $archivo);
                } else {
                    copy($fuente . "/" . $archivo, $destino . "/" . $archivo);
                }
            }
        }
        closedir($dir);
    } else {
        copy($fuente, $destino);
    }
}

function multiexplode($delimiters,$string) {   
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}


generate();