<?php require_once "includes/conexion.php";
PermitirAcceso(1002);
$sw = 0; //Para saber si ya se selecciono un cliente y mostrar la información
$PermitirListas = false;

//Filtros
$Filtro = ""; //Filtro
if (isset($_GET['BuscarDatoArt']) && $_GET['BuscarDatoArt'] != "") {
    $BuscarDatosArt = trim($_GET['BuscarDatoArt']);
    $BuscarDatosArt = str_replace(" ", "%", $BuscarDatosArt);
    $Filtro = "Where (ItemCode LIKE '%" . $BuscarDatosArt . "%' OR ItemName LIKE '%" . $BuscarDatosArt . "%' OR FrgnName LIKE '%" . $BuscarDatosArt . "%' OR SuppCatNum LIKE '%" . $BuscarDatosArt . "%')";

    // Combo ListaPrecios. SMM, 27/04/2023
    if (isset($_GET['ListaPrecio']) && $_GET['ListaPrecio'] != "") {
        $Filtro .= " AND [PriceList]='" . $_GET['ListaPrecio'] . "'";
        $sw = 1;
    }

    $Campos = "ItemCode, SuppCatNum, ItemName, ItmsGrpNam, CDU_Marca, Stock, PriceTax, Estado, NombreEstado";
    $Campos .= ($PermitirListas) ? ", ListName" : "";

    // SMM, 28/04/2023
    $VistaArticulos = ($PermitirListas) ? "uvw_Sap_tbl_ArticulosTodos_ListaPrecios" : "uvw_Sap_tbl_ArticulosTodos";

    $Cons = "Select $Campos From $VistaArticulos $Filtro";
    // echo $Cons;

    $SQL = sqlsrv_query($conexion, $Cons);
    $sw = 1;
}

// Combo ListaPrecios. SMM, 27/04/2023
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*', '', 'IdListaPrecio');
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Consultar artículos | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<?php
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_ArtAdd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El articulo ha sido creado exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_ArtUpd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El ID de servicio ha sido actualizado exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
?>
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
                    <h2>Consultar artículos</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
                        <li>
                            <a href="#">Gestión de artículos</a>
                        </li>
                        <li class="active">
                            <strong>Consultar artículos</strong>
                        </li>
                    </ol>
                </div>
            </div>
         <div class="wrapper wrapper-content">
             <div class="row">
				<div class="col-lg-12">
			    <div class="ibox-content">
					<?php include "includes/spinner.php";?>
				  <form action="consultar_articulos.php" method="get" id="formBuscar" class="form-horizontal">
						<div class="form-group">
							<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
						</div>

					  	<div class="form-group">
							<?php if ($PermitirListas) {?>
								<label class="col-lg-1 control-label">Lista Precio</label>
								<div class="col-lg-3">
									<select name="ListaPrecio" class="form-control" id="ListaPrecio">
										<?php if (false) {?><option value="">(Todos)</option><?php }?>

										<?php while ($row_ListaPrecios = sqlsrv_fetch_array($SQL_ListaPrecios)) {?>
											<option value="<?php echo $row_ListaPrecios['IdListaPrecio']; ?>" <?php if ((isset($_GET['ListaPrecio'])) && (strcmp($row_ListaPrecios['IdListaPrecio'], $_GET['ListaPrecio']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ListaPrecios['DeListaPrecio']; ?></option>
										<?php }?>
									</select>
								</div>
							<?php }?>

							<label class="col-lg-1 control-label">Buscar</label>
							<div class="col-lg-4">
								<input name="BuscarDatoArt" type="text" class="form-control" id="BuscarDatoArt" maxlength="100" placeholder="Consulte el ID o cualquier dato del artículo" value="<?php if (isset($_GET['BuscarDatoArt']) && ($_GET['BuscarDatoArt'] != "")) {echo $_GET['BuscarDatoArt'];}?>">
							</div>
							<div class="col-lg-1">
								<button type="submit" class="btn btn-outline btn-info"><i class="fa fa-search"></i> Buscar</button>
							</div>
						</div>
				 </form>
			</div>
			</div>
		  </div>
         <br>
			 <?php //echo $Cons;?>
		<?php if ($sw == 1) {?>
          <div class="row">
           <div class="col-lg-12">
			    <div class="ibox-content">
					<?php include "includes/spinner.php";?>
			<div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover dataTables-example" >
                    <thead>
                    <tr>
						<th>Código artículo</th>
						<th>Código art proveedor</th> <!-- // NEDUGA, 24/02/2022 -->

						<?php if ($PermitirListas) {?>
							<th>Lista de precios</th>
						<?php }?>

						<th>Nombre articulo</th>
						<th>Grupo de articulo</th>
						<th>Marca</th> <!-- SMM, 17/04/2023 -->
						<th>Stock</th>
						<th>Precio de venta</th> <!-- SMM, 17/04/2023 -->
						<th>Estado</th>
						<th>Acciones</th>
					</tr>
                    </thead>
                    <tbody>
                    <?php while ($row = sqlsrv_fetch_array($SQL)) {?>
						 <tr class="gradeX">
								<td><?php echo $row['ItemCode']; ?></td>
								<td><?php echo $row['SuppCatNum']; ?></td> <!-- // NEDUGA, 24/02/2022 -->

								<?php if ($PermitirListas) {?>
									<td><?php echo $row['ListName'] ?? ""; ?></td>
								<?php }?>

								<td><?php echo $row['ItemName']; ?></td>
								<td><?php echo $row['ItmsGrpNam']; ?></td>
								<td><?php echo $row['CDU_Marca'] ?? ""; ?></td> <!-- SMM, 17/04/2023 -->
								<td><?php echo number_format($row['Stock'], 2); ?></td>
								<td><?php echo number_format($row['PriceTax'] ?? 0, 2); ?></td> <!-- SMM, 17/04/2023 -->
								<td><span <?php if ($row['Estado'] == 'Y') {echo "class='label label-info'";} else {echo "class='label label-danger'";}?>><?php echo $row['NombreEstado']; ?></span></td>
								<td><a href="articulos.php?id=<?php echo base64_encode($row['ItemCode']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_articulos.php'); ?>" class="alkin btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a></td>
							</tr>
					<?php }?>
                    </tbody>
                    </table>
              </div>
			</div>
			 </div>
          </div>
		<?php }?>
        </div>
        <!-- InstanceEndEditable -->
        <?php include_once "includes/footer.php";?>

    </div>
</div>
<?php include_once "includes/pie.php";?>
<!-- InstanceBeginEditable name="EditRegion4" -->
 <script>
        $(document).ready(function(){
			 $("#formBuscar").validate({
				 submitHandler: function(form){
					 $('.ibox-content').toggleClass('sk-loading');
					 form.submit();
				}
			});
			$(".btn-link").on('click', function(){
				$('.ibox-content').toggleClass('sk-loading');
			});
            $('.dataTables-example').DataTable({
                pageLength: 25,
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

            });

        });

    </script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>
