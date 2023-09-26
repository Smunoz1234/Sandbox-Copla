<?php require_once "includes/conexion.php";
PermitirAcceso(1202);
$sw = 0;

// SMM, 25/11/2022
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimCode=$DimSeries");
$row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones);
$Nombre_DimSeries = $row_Dimension["DimName"];

// SMM, 16/02/2023
$OcrId = ($DimSeries == 1) ? "" : $DimSeries;
$Sucursal = $_GET['Sucursal'] ?? "";

//Estado actividad
$SQL_Estado = Seleccionar('uvw_tbl_EstadoDocSAP', '*');

//Series de documento
$ParamSerie = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'1250000001'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Estado autorizacion
$SQL_EstadoAuth = Seleccionar('uvw_Sap_tbl_EstadosAuth', '*');

// Estado autorizacion SAP. SMM, 10/12/2022
$SQL_EstadoAutorizacion = Seleccionar('tbl_EstadoAutorizacionesSAPB1', '*');

//Empleados
$SQL_Empleado = Seleccionar('uvw_Sap_tbl_EmpleadosSN', '*', '', 'NombreEmpleado');

//Tipo entrega
$SQL_TipoEntrega = Seleccionar('uvw_Sap_tbl_TipoEntrega', '*', '', 'DeTipoEntrega');

//Año entrega
$SQL_AnioEntrega = Seleccionar('uvw_Sap_tbl_TipoEntregaAnio', '*', '', 'DeAnioEntrega');

//Fechas
if (isset($_GET['FechaInicial']) && $_GET['FechaInicial'] != "") {
    $FechaInicial = $_GET['FechaInicial'];
    $sw = 1;
} else {
    //Restar 7 dias a la fecha actual
    $fecha = date('Y-m-d');
    $nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasDocSAP") . ' day');
    $nuevafecha = date('Y-m-d', $nuevafecha);
    $FechaInicial = $nuevafecha;
}
if (isset($_GET['FechaFinal']) && $_GET['FechaFinal'] != "") {
    $FechaFinal = $_GET['FechaFinal'];
} else {
    $FechaFinal = date('Y-m-d');
}

//Filtros
$Filtro = ""; //Filtro
if (isset($_GET['Estado']) && $_GET['Estado'] != "") {
    $Filtro .= " and Cod_Estado='" . $_GET['Estado'] . "'";
}

// SMM, 10/12/2022
if (isset($_GET['Autorizacion']) && $_GET['Autorizacion'] != "") {
    $Filtro .= " and AuthPortal='" . $_GET['Autorizacion'] . "'";
}

if (isset($_GET['AutorizacionSAP']) && $_GET['AutorizacionSAP'] != "") {
    $Filtro .= " AND IdEstadoAutorizacion = '" . $_GET['AutorizacionSAP'] . "'";
}
// Hasta aquí, 10/12/2022

// Filtrar por perfil. SMM, 23/12/2022
$Where_PerfilesAutorizador = "ID_Usuario='" . $_SESSION['CodUser'] . "'";
$SQL_Perfiles = Seleccionar('uvw_tbl_UsuariosPerfilesAsignados', '*', $Where_PerfilesAutorizador);

if (isset($_GET['PerfilAutor'])) {
    if ($_GET['PerfilAutor'] != "") {
        $Filtro .= " AND ID_PerfilUsuario_Creacion = '" . $_GET['PerfilAutor'] . "'";
    } else {
        // Todos los perfiles asignados
        $Filtro .= "AND ID_PerfilUsuario_Creacion IN (";
        $Perfiles = array();
        while ($Perfil = sqlsrv_fetch_array($SQL_Perfiles)) {
            $Perfiles[] = $Perfil['IdPerfil'];
        }

        $Perfiles[] = $_SESSION['Perfil']; // Agrego el perfil del usuario

        $Filtro .= implode(",", $Perfiles);
        $Filtro .= ")";
        // SMM, 20/01/2023

        // Volver a llenar la consulta SQL.
        $SQL_Perfiles = Seleccionar('uvw_tbl_UsuariosPerfilesAsignados', '*', $Where_PerfilesAutorizador);
    }
}
// Hasta aquí, 23/12/2022

if (isset($_GET['Cliente']) && $_GET['Cliente'] != "") {
    $Filtro .= " and CardCode='" . $_GET['Cliente'] . "'";
}

if (isset($_GET['Empleado']) && $_GET['Empleado'] != "") {
    $Filtro .= " and CodEmpleado='" . $_GET['Empleado'] . "'";
}

if (isset($_GET['TipoEntrega']) && $_GET['TipoEntrega'] != "") {
    $Filtro .= " and IdTipoEntrega='" . $_GET['TipoEntrega'] . "'";
}

// SMM, 16/02/2023
if ($Sucursal != "") {
    $Filtro .= " AND OcrCode$OcrId='$Sucursal'";
}

if (isset($_GET['AnioEntrega']) && $_GET['AnioEntrega'] != "") {
    $Filtro .= " and IdAnioEntrega='" . $_GET['AnioEntrega'] . "'";
}

if (isset($_GET['EntregaDescont']) && $_GET['EntregaDescont'] != "") {
    $Filtro .= " and Descontable='" . $_GET['EntregaDescont'] . "'";
}

if (isset($_GET['Series']) && $_GET['Series'] != "") {
    $Filtro .= " and [IdSeries]='" . $_GET['Series'] . "'";
    $SQL_Sucursal = SeleccionarGroupBy('uvw_Sap_tbl_SeriesSucursalesAlmacenes', 'IdSucursal, DeSucursal', "IdSerie='" . $_GET['Series'] . "'", "IdSucursal, DeSucursal");
    $sw = 1;
} else {
    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }

    // Comentar para no filtrar por serie.
    $Filtro .= " and [IdSeries] IN (" . $FilSerie . ")";

    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
}

if (isset($_GET['BuscarDato']) && $_GET['BuscarDato'] != "") {
    $Filtro .= " and (DocNum LIKE '%" . $_GET['BuscarDato'] . "%' OR NombreContacto LIKE '%" . $_GET['BuscarDato'] . "%' OR DocNumLlamadaServicio LIKE '%" . $_GET['BuscarDato'] . "%' OR ID_LlamadaServicio LIKE '%" . $_GET['BuscarDato'] . "%' OR IdDocPortal LIKE '%" . $_GET['BuscarDato'] . "%' OR NombreEmpleadoVentas LIKE '%" . $_GET['BuscarDato'] . "%' OR Comentarios LIKE '%" . $_GET['BuscarDato'] . "%')";
}

$Cons = "SELECT * FROM uvw_Sap_tbl_SolicitudesSalidas_Borrador WHERE (DocDate BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro ORDER BY DocNum DESC";

// SMM, 09/12/2022
if (isset($_GET['DocNum']) && $_GET['DocNum'] != "") {
    $Where = "DocNum LIKE '%" . trim($_GET['DocNum']) . "%'";

    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }

    // Comentar para no filtrar por serie.
    // $Where .= " and [IdSeries] IN (" . $FilSerie . ")";

    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

    $Cons = "Select * From uvw_Sap_tbl_SolicitudesSalidas_Borrador Where $Where";
}

// SMM, 03/04/2023
if ($sw == 1) {
    // echo $Cons;
    $SQL = sqlsrv_query($conexion, $Cons);
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Consultar solicitud de traslado borrador | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<?php
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_SolSalAdd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Solicitud de salida ha sido agregada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
} // useless

if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_SolSalUpd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Solicitud de salida Borrador ha sido actualizada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}

// SMM, 16/12/2022
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_DefinitivoAdd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El Documento Definitivo se ha creado exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
?>
<script type="text/javascript">
	$(document).ready(function() {
		$("#NombreCliente").change(function(){
			var NomCliente=document.getElementById("NombreCliente");
			var Cliente=document.getElementById("Cliente");
			if(NomCliente.value==""){
				Cliente.value="";
			}
		});

		$("#Series").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Serie=document.getElementById('Series').value;
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=19&id="+Serie+"&todos=1",
				success: function(response){
					$('#Sucursal').html(response).fadeIn();

					// SMM, 16/02/2023
					<?php if (isset($_GET['Sucursal'])) {?>
						$('#Sucursal').val("<?php echo $_GET['Sucursal']; ?>");
					<?php }?>

					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
		});

		$("#TipoEntrega").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var TipoEnt=document.getElementById('TipoEntrega').value;
			var AnioEntrega=document.getElementById('AnioEntrega');
			var EntregaDescont=document.getElementById('EntregaDescont');

			if(TipoEnt==2||TipoEnt==3||TipoEnt==4){
				document.getElementById('dv_AnioEnt').style.display='block';
				document.getElementById('dv_Descont').style.display='none';
				EntregaDescont.value="";
			}else if(TipoEnt==6){
				document.getElementById('dv_AnioEnt').style.display='none';
				document.getElementById('dv_Descont').style.display='block';
				AnioEntrega.value="";
			}else{
				document.getElementById('dv_AnioEnt').style.display='none';
				document.getElementById('dv_Descont').style.display='none';
				AnioEntrega.value="";
				EntregaDescont.value="";
			}
			$('.ibox-content').toggleClass('sk-loading',false);
		});

	});
</script>
<!-- InstanceEndEditable -->
</head>

<body>

<div id="wrapper">

    <?php include_once "includes/menu.php";?>

    <div id="page-wrapper" class="gray-bg">
        <?php include_once "includes/menu_superior.php";?>
        <!-- InstanceBeginEditable name="Contenido" -->
        <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Consultar solicitud de traslado borrador</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
                        <li>
                            <a href="#">Inventario</a>
                        </li>
                        <li>
                            <a href="#">Consultas</a>
                        </li>
                        <li class="active">
                            <strong>Consultar solicitud de traslado borrador</strong>
                        </li>
                    </ol>
                </div>
            </div>
         <div class="wrapper wrapper-content">
             <div class="row">
				<div class="col-lg-12">
			    <div class="ibox-content">
					 <?php include "includes/spinner.php";?>
				  <form action="consultar_solicitud_salida_borrador.php" method="get" id="formBuscar" class="form-horizontal">
						<div class="form-group">
							<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
						</div>
						<div class="form-group">
							<label class="col-lg-1 control-label">Fechas</label>
							<div class="col-lg-3">
								<div class="input-daterange input-group" id="datepicker">
									<input name="FechaInicial" type="text" autocomplete="off" class="input-sm form-control" id="FechaInicial" placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>"/>
									<span class="input-group-addon">hasta</span>
									<input name="FechaFinal" type="text" autocomplete="off" class="input-sm form-control" id="FechaFinal" placeholder="Fecha final" value="<?php echo $FechaFinal; ?>" />
								</div>
							</div>
							<label class="col-lg-1 control-label">Estado</label>
							<div class="col-lg-3">
								<select name="Estado" class="form-control" id="Estado">
										<option value="">(Todos)</option>
								  <?php while ($row_Estado = sqlsrv_fetch_array($SQL_Estado)) {?>
										<option value="<?php echo $row_Estado['Cod_Estado']; ?>" <?php if ((isset($_GET['Estado'])) && (strcmp($row_Estado['Cod_Estado'], $_GET['Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Estado['NombreEstado']; ?></option>
								  <?php }?>
								</select>
							</div>
							<label class="col-lg-1 control-label">Serie</label>
							<div class="col-lg-3">
								<select name="Series" class="form-control" id="Series">
										<option value="">(Todos)</option>
								  <?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {?>
										<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if ((isset($_GET['Series'])) && (strcmp($row_Series['IdSeries'], $_GET['Series']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Series['DeSeries']; ?></option>
								  <?php }?>
								</select>
							</div>
						</div>
					  	<div class="form-group">
							<label class="col-lg-1 control-label">Cliente</label>
							<div class="col-lg-3">
								<input name="Cliente" type="hidden" id="Cliente" value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {echo $_GET['Cliente'];}?>">
								<input name="NombreCliente" type="text" class="form-control" id="NombreCliente" placeholder="Para TODOS, dejar vacio..." value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {echo $_GET['NombreCliente'];}?>">
							</div>
							<label class="col-lg-1 control-label"><?php echo $Nombre_DimSeries; ?></label>
							<div class="col-lg-3">
								<select name="Sucursal" class="form-control" id="Sucursal">
									<option value="">(Todos)</option>
								  <?php if (isset($_GET['Series']) && ($_GET['Series'] != "")) {
    while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {?>
											<option value="<?php echo $row_Sucursal['IdSucursal']; ?>" <?php if (isset($_GET['Sucursal']) && (strcmp($row_Sucursal['IdSucursal'], $_GET['Sucursal']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Sucursal['DeSucursal']; ?></option>
									<?php }
}?>
								</select>
							</div>
							<label class="col-lg-1 control-label">Buscar dato</label>
							<div class="col-lg-3">
								<input name="BuscarDato" type="text" class="form-control" id="BuscarDato" maxlength="100" value="<?php if (isset($_GET['BuscarDato']) && ($_GET['BuscarDato'] != "")) {echo $_GET['BuscarDato'];}?>">
							</div>
						</div>
					  	<div class="form-group">
							<label class="col-lg-1 control-label">Solicitado para</label>
							<div class="col-lg-3">
								<select name="Empleado" class="form-control select2" id="Empleado">
										<option value="">(Todos)</option>
								  <?php while ($row_Empleado = sqlsrv_fetch_array($SQL_Empleado)) {?>
										<option value="<?php echo $row_Empleado['ID_Empleado']; ?>" <?php if ((isset($_GET['Empleado'])) && (strcmp($row_Empleado['ID_Empleado'], $_GET['Empleado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Empleado['NombreEmpleado']; ?></option>
								  <?php }?>
								</select>
							</div>
							<label class="col-lg-1 control-label">Tipo entrega</label>
							<div class="col-lg-3">
								<select name="TipoEntrega" class="form-control" id="TipoEntrega">
										<option value="">(Todos)</option>
								  <?php while ($row_TipoEntrega = sqlsrv_fetch_array($SQL_TipoEntrega)) {?>
										<option value="<?php echo $row_TipoEntrega['IdTipoEntrega']; ?>" <?php if ((isset($_GET['TipoEntrega'])) && (strcmp($row_TipoEntrega['IdTipoEntrega'], $_GET['TipoEntrega']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_TipoEntrega['DeTipoEntrega']; ?></option>
								  <?php }?>
								</select>
							</div>
							<div id="dv_AnioEnt" style="display: none;">
								<label class="col-lg-1 control-label">Año entrega</label>
								<div class="col-lg-2">
									<select name="AnioEntrega" class="form-control" id="AnioEntrega">
										<option value="">(Todos)</option>
									<?php while ($row_AnioEntrega = sqlsrv_fetch_array($SQL_AnioEntrega)) {?>
											<option value="<?php echo $row_AnioEntrega['IdAnioEntrega']; ?>" <?php if ((isset($_GET['AnioEntrega'])) && (strcmp($row_AnioEntrega['IdAnioEntrega'], $_GET['AnioEntrega']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_AnioEntrega['DeAnioEntrega']; ?></option>
									<?php }?>
									</select>
								</div>
							</div>
							<div id="dv_Descont" style="display: none;">
								<label class="col-lg-1 control-label">Entrega descontable</label>
								<div class="col-lg-2">
									<select name="EntregaDescont" class="form-control" id="EntregaDescont">
										<option value="">(Todos)</option>
										<option value="NO" <?php if ((isset($_GET['EntregaDescont'])) && ($_GET['EntregaDescont'] == "NO")) {echo "selected=\"selected\"";}?>>NO</option>
										<option value="SI" <?php if ((isset($_GET['EntregaDescont'])) && ($_GET['EntregaDescont'] == "SI")) {echo "selected=\"selected\"";}?>>SI</option>
									</select>
								</div>
							</div>

							<!-- Número de documento -->
							<label class="col-lg-1 control-label">Número documento</label>
							<div class="col-lg-3">
								<input name="DocNum" type="text" class="form-control" id="DocNum" maxlength="50" placeholder="Digite un número completo, o una parte del mismo..." value="<?php if (isset($_GET['DocNum']) && ($_GET['DocNum'] != "")) {echo $_GET['DocNum'];}?>">
							</div>
							<!-- SMM, 09/12/2022 -->
						</div>

						<div class="form-group">
							<!-- SMM, 10/12/2022 -->
							<label class="col-lg-1 control-label">Autorización Portal One</label>
							<div class="col-lg-3">
								<select name="Autorizacion" class="form-control" id="Autorizacion">
										<option value="">(Todos)</option>
								   <?php while ($row_EstadoAuth = sqlsrv_fetch_array($SQL_EstadoAuth)) {?>
										<option value="<?php echo $row_EstadoAuth['IdAuth']; ?>" <?php if (isset($_GET['Autorizacion']) && (strcmp($row_EstadoAuth['IdAuth'], $_GET['Autorizacion']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoAuth['DeAuth']; ?></option>
								  <?php }?>
								</select>
							</div>

							<label class="col-lg-1 control-label">Autorización SAP B1</label>
							<div class="col-lg-3">
								<select name="AutorizacionSAP" class="form-control" id="AutorizacionSAP">
										<option value="">(Todos)</option>
								   <?php while ($row_EstadoAutorizacion = sqlsrv_fetch_array($SQL_EstadoAutorizacion)) {?>
										<option value="<?php echo $row_EstadoAutorizacion['IdEstadoAutorizacion']; ?>" <?php if (isset($_GET['AutorizacionSAP']) && (strcmp($row_EstadoAutorizacion['IdEstadoAutorizacion'], $_GET['AutorizacionSAP']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoAutorizacion['EstadoAutorizacion']; ?></option>
								  <?php }?>
								</select>
							</div>
							<!-- Hasta aquí, 10/12/2022 -->

							<!-- SMM, 23/12/2022 -->
							<label class="col-lg-1 control-label">Perfil Autor</label>
							<div class="col-lg-3">
								<select name="PerfilAutor" class="form-control" id="PerfilAutor">
										<option value="">(Todos)</option>
								   <?php while ($row_Perfil = sqlsrv_fetch_array($SQL_Perfiles)) {?>
										<option value="<?php echo $row_Perfil['IdPerfil']; ?>" <?php if (isset($_GET['PerfilAutor']) && (strcmp($row_Perfil['IdPerfil'], $_GET['PerfilAutor']) == 0)) {echo "selected";}?>><?php echo $row_Perfil['DePerfil']; ?></option>
								  <?php }?>
								</select>
							</div>
							<!-- Hasta aquí, 23/12/2022 -->
						</div>

						<div class="form-group">
							<div class="col-lg-12">
								<button type="submit" class="btn btn-outline btn-success pull-right"><i class="fa fa-search"></i> Buscar</button>
							</div>
						</div>

						<?php if ($sw == 1) {?>
					  	<div class="form-group">
							<div class="col-lg-10 col-md-10">
								<a href="exportar_excel.php?exp=4&Cons=<?php echo base64_encode($Cons); ?>">
									<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel" title="Exportar a Excel"/>
								</a>
							</div>
						</div>
					  	<?php }?>
				 </form>
			</div>
			</div>
		  </div>
         <br>
			 <?php //echo $Cons;?>
          <div class="row">
           <div class="col-lg-12">
			    <div class="ibox-content">
					 <?php include "includes/spinner.php";?>
			<div class="table-responsive">
                <table class="table table-striped table-bordered table-hover dataTables-example" >
                    <thead>
                    <tr>
						<th>ID Borrador</th> <!-- SMM, 10/12/2022 -->

						<th>Número</th>
						<th>Serie</th>
						<th>Sucursal</th>
						<th>Fecha solicitud</th>
						<th>Solicitado para</th>
						<th>Tipo entrega</th>

						<th>Usuario Autoriza Portal One</th> <!-- SMM, 14/12/2022 -->
						<th>No. Documento Definitivo</th><!-- SMM, 14/12/2022 -->

						<th>Comentarios</th> <!-- SMM, 25/11/2022 -->
						<th>Descontable</th>

						<!-- th>Documento destino</th -->

						<th>Usuario Creación/Autor</th>
						<th>Perfil Autor</th> <!-- SMM, 23/12/2022 -->

						<th>Usuario Actualización</th>

						<th>Estado</th>

						<th>Estado Autorización Portal One</th> <!-- SMM, 14/12/2022 -->
						<th>Estado Autorización SAP B1</th> <!-- SMM, 14/12/2022 -->

						<th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($sw == 1) {?>
    					<?php while ($row = sqlsrv_fetch_array($SQL)) {?>
						<tr class="gradeX">
						 	<td><?php echo $row['ID_SolSalida']; ?></td> <!-- SMM, 10/12/2022 -->

							<td><?php echo $row['DocNum']; ?></td>
							<td><?php echo $row['DeSeries']; ?></td>
							<td><?php echo $row['OcrName3']; ?></td>
							<td><?php echo $row['DocDate']; ?></td>
							<td><?php echo $row['NomEmpleado']; ?></td>
							<td><?php echo $row['DeTipoEntrega']; ?></td>

							<!-- SMM, 14/12/2022 -->
							<td><?php echo $row['UsuarioAutoriza']; ?></td>
							<td>
								<?php if (isset($row["DocEntryDocumentoDefinitivo"]) && isset($row["DocNumDocumentoDefinitivo"])) {?>
									<a target="_blank" href="solicitud_salida.php?id=<?php echo base64_encode($row['DocEntryDocumentoDefinitivo']); ?>&id_portal=<?php echo base64_encode($row['DocNumDocumentoDefinitivo']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_solicitud_salida_borrador.php'); ?>" class="Xbtn Xbtn-success Xbtn-xs"><?php echo $row["DocNumDocumentoDefinitivo"]; ?></a>
								<?php } else {echo "--";}?>
							</td>

							<td><?php echo SubComent($row['Comentarios']); ?></td> <!-- SMM, 25/11/2022 -->
							<td><?php echo $row['Descontable']; ?></td>

							<!-- Se elimino el documento -->

							<td><?php echo $row['UsuarioCreacion']; ?></td> <!-- Autor -->
							<td><?php echo $row['PerfilUsuario_Creacion'] ?? ""; ?></td> <!-- Autor -->

							<td><?php echo $row['UsuarioActualizacion']; ?></td>
							<td><span <?php if ($row['Cod_Estado'] == 'O') {echo "class='label label-info'";} else {echo "class='label label-danger'";}?>><?php echo $row['NombreEstado']; ?></span></td>

							<td> <!-- SMM, 14/12/2022 -->
								<span <?php if ($row['AuthPortal'] == 'Y') {echo "class='label label-info'";} elseif ($row['AuthPortal'] == 'P') {echo "class='label label-warning'";} elseif ($row['AuthPortal'] == 'R') {echo "class='label label-danger'";} else {echo "class='label label-secondary'";}?>>
									<?php echo $row['DeAuthPortal'] ?? "N/A"; ?>
								</span>
							</td>
							<td> <!-- SMM, 14/12/2022 -->
								<span class="label" style="background-color: <?php echo (isset($row['ColorEstadoAutorizacion']) && ($row['ColorEstadoAutorizacion'] != "")) ? $row['ColorEstadoAutorizacion'] : "darkgray"; ?>; color: white;">
									<?php echo (isset($row['EstadoAutorizacion']) && ($row['EstadoAutorizacion'] != "")) ? $row['EstadoAutorizacion'] : "No Aplica"; ?>
								</span>
							</td>

							<td>
								<a href="solicitud_salida_borrador.php?id=<?php echo base64_encode($row['ID_SolSalida']); ?>&id_portal=<?php echo base64_encode($row['IdDocPortal']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_solicitud_salida_borrador.php'); ?>" class="alkin btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a>
								<a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row['ID_SolSalida']); ?>&ObType=<?php echo base64_encode('1250000001'); ?>&IdFrm=<?php echo base64_encode($row['IdSeries']); ?>" target="_blank" class="btn btn-warning btn-xs"><i class="fa fa-download"></i> Descargar</a>
							</td>
						</tr>
						<?php }?>
					<?php }?>
                    </tbody>
                </table>
              </div>
			</div>
			 </div>
          </div>
        </div>
        <!-- InstanceEndEditable -->
        <?php include_once "includes/footer.php";?>

    </div>
</div>
<?php include_once "includes/pie.php";?>
<!-- InstanceBeginEditable name="EditRegion4" -->
 <script>
        $(document).ready(function(){
			// SMM, 16/02/2023
			<?php if (isset($_GET['Series']) && ($_GET['Series'] != "")) {?>
				$('#Series').trigger('change');
			<?php }?>

			$("#formBuscar").validate({
			 submitHandler: function(form){
				 $('.ibox-content').toggleClass('sk-loading');
				 form.submit();
				}
			});
			 $(".alkin").on('click', function(){
					$('.ibox-content').toggleClass('sk-loading');
				});
			 $(".select2").select2();
			 $('#FechaInicial').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
            });
			 $('#FechaFinal').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
            });

			$('.chosen-select').chosen({width: "100%"});

			<?php if (isset($_GET['TipoEntrega'])) {?>
			$('#TipoEntrega').trigger('change');
			<?php }?>

			var options = {
				url: function(phrase) {
					return "ajx_buscar_datos_json.php?type=7&id="+phrase;
				},

				getValue: "NombreBuscarCliente",
				requestDelay: 400,
				list: {
					match: {
						enabled: true
					},
					onClickEvent: function() {
						var value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
						$("#Cliente").val(value);
					}
				}
			};

			$("#NombreCliente").easyAutocomplete(options);

            $('.dataTables-example').DataTable({
                pageLength: 25,
                responsive: false,
                dom: '<"html5buttons"B>lTfgitp',
				language: {
					"decimal":        "",
					"emptyTable":     "No se encontraron resultados.",
					"info":           "Mostrando _START_ - _END_ de _TOTAL_ registros",
					"infoEmpty":      "Mostrando 0 - 0 de 0 registros",
					"infoFiltered":   "(filtrando de _MAX_ registros)",
					"infoPostFix":    "",
					"thousands":      ",",
					"lengthMenu":     "Mostrar _MENU_ registros",
					"loadingRecords": "Cargando...",
					"processing":     "Procesando...",
					"search":         "Filtrar:",
					"zeroRecords":    "Ningún registro encontrado",
					"paginate": {
						"first":      "Primero",
						"last":       "Último",
						"next":       "Siguiente",
						"previous":   "Anterior"
					},
					"aria": {
						"sortAscending":  ": Activar para ordenar la columna ascendente",
						"sortDescending": ": Activar para ordenar la columna descendente"
					}
				},
                buttons: []
				, order: [[ 0, "desc" ]] // SMM, 15/12/2022
            });

        });

    </script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>
