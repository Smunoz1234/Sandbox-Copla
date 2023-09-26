<?php  
require_once("includes/conexion.php");
if(isset($_GET['id'])&&$_GET['id']!=""){
	$IdItemCode=base64_decode($_GET['id']);
}
//Lista de materiales
	$SQL_ListaMateriales=Seleccionar('uvw_Sap_tbl_ArticulosLlamadasDetalle','*',"IdArticuloPadre='".$IdItemCode."'");

?>
<div class="form-group">
	<div class="col-lg-12">
		<div class="row m-b-md">
			<div class="col-lg-12">
				<a href="lista_materiales.php?id=<?php echo base64_encode($IdItemCode);?>&tl=1" target="_blank" class="btn btn-success"><i class="fa fa-edit"></i> Ver Lista de materiales</a>
				<a style="margin-left: 15px;" href="lista_materiales.php?ItemCode=<?php echo base64_encode($IdItemCode);?>" target="_blank" class="btn btn-info"><i class="fa fa-plus"></i> Crear Lista de materiales</a>
			</div>
		</div>
		<div class="table-responsive">
		<table class="footable table table-stripped toggle-arrow-tiny">
			<thead>
			<tr>
				<th>Código</th>
				<th>Descripción</th>
				<th>Unidad</th>
				<th>Cantidad</th>
				<th>Cant. Litros</th>
				<th>Almacén</th>
				<th data-breakpoints="all">Servicio</th>
				<th data-breakpoints="all">Metodo aplic</th>
				<th data-breakpoints="all">Tipo plaga</th>
				<th data-breakpoints="all">Áreas Ctrl</th>
			</tr>
			</thead>
			<tbody>
			<?php while($row_ListaMateriales=sqlsrv_fetch_array($SQL_ListaMateriales)){?>
				 <tr>
					<td><?php echo $row_ListaMateriales['IdArticuloHijo'];?></td>
					<td><?php echo $row_ListaMateriales['DeArticuloHijo'];?></td>
					<td><?php echo $row_ListaMateriales['SalUnitMsr'];?></td>
					<td><?php echo number_format($row_ListaMateriales['Cantidad'],2);?></td>
					<td><?php echo number_format($row_ListaMateriales['CantLitros'],2);?></td>
					<td><?php echo $row_ListaMateriales['WhsName'];?></td>
					<td><?php echo $row_ListaMateriales['DeServicio'];?></td>
					<td><?php echo $row_ListaMateriales['DeMetodoAplicacion'];?></td>
					<td><?php echo $row_ListaMateriales['DeTipoPlagas'];?></td>
					<td><?php echo $row_ListaMateriales['CDU_AreasCtrl'];?></td>
				</tr>
			<?php }?>
			</tbody>
		</table>
  		</div>
	</div>
</div>	
<script>
 $(document).ready(function(){
	$('.footable').footable();
 });
</script>