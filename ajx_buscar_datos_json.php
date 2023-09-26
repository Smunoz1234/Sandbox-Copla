<?php
if ((isset($_GET['type']) && ($_GET['type'] != "")) || (isset($_POST['type']) && ($_POST['type'] != ""))) {
    require_once "includes/conexion.php";
    header('Content-Type: application/json');
    if (isset($_GET['type']) && ($_GET['type'] != "")) {
        $type = $_GET['type'];
    } else {
        $type = $_POST['type'];
    }

    if ($type == 1) { //Buscar direccion y barrio dependiendo de la sucursal
        $Consulta = "Select * From uvw_Sap_tbl_Clientes_Sucursales Where TipoDireccion='S' And CodigoCliente='" . $_GET['CardCode'] . "' and NombreSucursal='" . $_GET['Sucursal'] . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Direccion' => $row['Direccion'],
            'Ciudad' => $row['Ciudad'],
            'Barrio' => $row['Barrio'],
            'NombreContacto' => $row['NombreContacto'],
            'TelefonoContacto' => PermitirFuncion(512) ? $row['CelularCliente'] : $row['TelefonoContacto'],
            // SMM, 23/03/2022
            'CargoContacto' => $row['CargoContacto'],
            'CorreoContacto' => $row['CorreoContacto'],
        );
        echo json_encode($records);
    } elseif ($type == 2) { //Buscar datos internos cuando la actividad es de tipo Interna
        $Consulta = "Select Top 1 * From uvw_Sap_tbl_Clientes_Sucursales Where CodigoCliente='" . NIT_EMPRESA . "'";
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'CodigoCliente' => $row['CodigoCliente'],
            'NombreCliente' => $row['NombreCliente'],
            'NombreSucursal' => $row['NombreSucursal'],
            'Direccion' => $row['Direccion'],
            'Barrio' => $row['Barrio'],
        );
        echo json_encode($records);
    } elseif ($type == 3) { //Buscar direccion de facturacion dependiendo del cliente
        $Vista = "uvw_Sap_tbl_Clientes_Sucursales";
        if (isset($_GET['pv']) && ($_GET['pv'] == 1)) { //Proveedor
            $Vista = "uvw_Sap_tbl_Proveedores_Sucursales";
        }

        $Consulta = "Select * From $Vista Where CodigoCliente='" . $_GET['CardCode'] . "' And NombreSucursal='" . $_GET['Sucursal'] . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'NombreSucursal' => $row['NombreSucursal'],
            'Direccion' => $row['Direccion'],
            'Barrio' => $row['Barrio'],
            'TipoDireccion' => $row['TipoDireccion'],
        );
        echo json_encode($records);
    } elseif ($type == 4) { //Buscar direccion de destino dependiendo del cliente (no usado)
        $Consulta = "Select * From uvw_Sap_tbl_Clientes_Sucursales Where CodigoCliente='" . $_GET['CardCode'] . "' and TipoDireccion='S'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'NombreSucursal' => $row['NombreSucursal'],
            'Direccion' => $row['Direccion'],
            'Barrio' => $row['Barrio'],
            'TipoDireccion' => $row['TipoDireccion'],
        );
        echo json_encode($records);
    } elseif ($type == 5) { //Buscar Telefono y correo del contacto
        $Consulta = "Select * From uvw_Sap_tbl_ClienteContactos Where CodigoContacto='" . $_GET['Contacto'] . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Telefono' => PermitirFuncion(512) ? $row['TelefonoCelular'] : $row['Telefono1'],
            // SMM, 23/03/2022
            'Correo' => $row['CorreoElectronico'],
        );
        echo json_encode($records);
    } elseif ($type == 6) { //Consultar grupo de articulos en la llamada de servicio
        $Consulta = "Select * From uvw_Sap_tbl_ArticulosLlamadas Where ItemCode='" . $_GET['id'] . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'ItemCode' => $row['ItemCode'],
            'ItmsGrpCod' => $row['ItmsGrpCod'],
            'ItmsGrpNam' => $row['ItmsGrpNam'],
            'NombreSucursal' => $row['NombreSucursal'],
            'Servicios' => $row['Servicios'],
            'Areas' => $row['Areas'],
            'NombreContacto' => $row['NombreContacto'],
            'TelefonoContacto' => $row['TelefonoContacto'],
            'CargoContacto' => $row['CargoContacto'],
            'CorreoContacto' => $row['CorreoContacto'],
            //            'Posicion' => $row['Posicion'],
            //            'DeOLT' => $row['DeOLT']
        );
        echo json_encode($records);
    } elseif ($type == 7) { //Consultar clientes
        if (isset($_GET['pv']) && ($_GET['pv'] == 1)) { //Mostrar proveedores
            $prov = 1;
        } else {
            $prov = 0;
        }
        if (isset($_GET['lead']) && ($_GET['lead'] == 1)) { //Incluir los leads
            $lead = 1;
        } else {
            $lead = 0;
        }
        $Param = array("'" . $_GET['id'] . "'", $_SESSION['CodUser'], $prov, $lead);
        $SQL = EjecutarSP('sp_ConsultarClientes', $Param);
        $records = array();
        $j = 0;
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$j] = array(
                'CodigoCliente' => $row['CodigoCliente'],
                'NombreCliente' => $row['NombreCliente'],
                'NombreBuscarCliente' => $row['NombreBuscarCliente'],
            );
            $j++;
        }
        echo json_encode($records);
    } elseif ($type == 8) { //Consultar municipios
        $Consulta = "Select * From uvw_Sap_tbl_SN_Municipio Where ID_Municipio LIKE '%" . $_GET['id'] . "%' OR DE_Municipio LIKE '%" . $_GET['id'] . "%'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $j = 0;
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$j] = array(
                'Codigo' => $row['ID_Municipio'],
                'Ciudad' => $row['DE_Municipio'],
                'Departamento' => $row['DeDepartamento'],
            );
            $j++;
        }
        echo json_encode($records);
    } elseif ($type == 9) { //Consultar si hay actividades nuevas asignadas
        $Consulta = "Select TOP 1 ID_Actividad From uvw_Sap_tbl_Actividades Where ID_EmpleadoActividad='" . $_GET['user'] . "' And FechaCreacion='" . date('Y-m-d') . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'ID' => $row['ID_Actividad'],
        );
        echo json_encode($records);
    } elseif ($type == 10) { //Consultar tipo de gestion cartera (Telefono o Direccion)
        $Consulta = "Select TipoDestino From uvw_tbl_Cartera_TipoGestion Where ID_TipoGestion='" . $_GET['tge'] . "'";
        //echo $Consulta;
        $SQL = sqlsrv_query($conexion, $Consulta);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'TDest' => $row['TipoDestino'],
        );
        echo json_encode($records);
    } elseif ($type == 11) { //Consultar los datos de las facturas vencidas con sus intereses en mora
        $Param = array("'" . base64_decode($_GET['CardCode']) . "'", $_GET['IntMora'], $_GET['FactNoVenc']);
        $SQL = EjecutarSP('sp_CalcularIntMoraFactVencida', $Param);
        $records = array();
        $SumIntMora = 0;
        $SumSaldo = 0;
        $SumGastoCob = 0;
        $SumCobPre = 0;
        while ($row = sqlsrv_fetch_array($SQL)) {
            $SumIntMora = $SumIntMora + $row['InteresesMora'];
            $SumSaldo = $SumSaldo + $row['SaldoDocumento'];
            $SumGastoCob = $SumGastoCob + $row['GastosCobranza'];
            $SumCobPre = $SumCobPre + $row['CobroPrejuridico'];
        }
        $records = array(
            'TotalSaldo' => $SumSaldo,
            'TotalIntMora' => $SumIntMora,
            'TotalGastosCob' => $SumGastoCob,
            'TotalCobroPre' => $SumCobPre,
        );
        echo json_encode($records);
    } elseif ($type == 12) { //Consultar articulos para grilla
        $Dato = $_GET['data'];
        $Almacen = isset($_GET['whscode']) ? $_GET['whscode'] : "";
        $TipoDoc = isset($_GET['tipodoc']) ? $_GET['tipodoc'] : 1;
        $SoloStock = isset($_GET['solostock']) ? $_GET['solostock'] : 1;
        $TodosArticulos = isset($_GET['todosart']) ? $_GET['todosart'] : 0;
        $IdListaPrecio = isset($_GET['idlistaprecio']) ? $_GET['idlistaprecio'] : ""; // NEDUGA, 24/02/2022

        $Param = array(
            "'" . $Dato . "'", // @DatoBuscar
            "'" . $Almacen . "'", // @WhsCode
            "'" . $TipoDoc . "'",
            "'" . $SoloStock . "'",
            "'" . $TodosArticulos . "'",
            "'" . $IdListaPrecio . "'", // @PriceList. NEDUGA, 24/02/2022
        );
        //$Param=array("'".$_GET['data']."'","'".$_GET['whscode']."'","'".$_GET['tipodoc']."'");

        // $SQL=EjecutarSP('sp_ConsultarArticulos',$Param); // Anterior
        $SQL = EjecutarSP('sp_ConsultarArticulos_ListaPrecios', $Param); // Nuevo

        $records = array();
        $j = 0;
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$j] = array(
                'IdArticulo' => $row['IdArticulo'],
                'CodArticuloProveedor' => $row['CodArticuloProveedor'],
                // NEDUGA, 24/02/2022
                'IdListaPrecio' => $row['IdListaPrecio'],
                // SMM, 24/02/2022
                'DescripcionArticulo' => $row['DescripcionArticulo'],
                'NombreBuscarArticulo' => $row['NombreBuscarArticulo'],
                'UndMedida' => $row['UndMedida'],
                'PrecioSinIVA' => number_format($row['PrecioSinIVA'], 2),
                'PrecioConIVA' => number_format($row['PrecioConIVA'], 2),
                'CodAlmacen' => $row['CodAlmacen'],
                'Almacen' => $row['Almacen'],
                'StockAlmacen' => number_format($row['StockAlmacen'], 2),
                'StockGeneral' => number_format($row['StockGeneral'], 2),
            );
            $j++;
        }
        echo json_encode($records);
    } elseif ($type == 13) { //Consultar URL del tipo de categoria
        $SQL = Seleccionar("uvw_tbl_TipoCategoria", "*", "ID_TipoCategoria='" . $_GET['TipoCat'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'URL' => $row['URL'],
        );
        echo json_encode($records);
    } elseif ($type == 14) { //Consultar datos del atributo en RADIUS (DIALNET - MySQL)
        require_once "includes/conexion_mysql.php";
        $SQL = Seleccionar("dictionary", "RecommendedOP, RecommendedTable, Value", "Attribute='" . $_GET['NomAtt'] . "'", '', '', 3);
        $records = array();
        $row = mysqli_fetch_array($SQL);
        $records = array(
            'RecommendedOP' => $row['RecommendedOP'],
            'RecommendedTable' => $row['RecommendedTable'],
            'Value' => $row['Value'],
        );
        mysqli_close($conexion_mysql);
        echo json_encode($records);
    } elseif ($type == 15) { //Consultar fecha de actualizacion de documentos de SAP B1
        $Param = array("'" . base64_decode($_GET['docentry']) . "'", "'" . $_GET['objtype'] . "'", "'" . $_GET['date'] . "'");
        $SQL = EjecutarSP('sp_ConsultarFechaActDocSAP', $Param);
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Result' => $row['Result'],
        );
        echo json_encode($records);
    } elseif ($type == 16) { //Consultar el comentario sugerido en la cartera de gestion
        $SQL = Seleccionar("uvw_tbl_Cartera_ResultadoGestion", "ID_ResultadoGestion, ComentariosSugeridos", "ID_ResultadoGestion='" . $_GET['Res'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Comentarios' => $row['ComentariosSugeridos'],
        );
        echo json_encode($records);
    } elseif ($type == 17) { //Consultar si todos los items con lote ya fueron seleccionados
        $Param = array("'" . $_GET['cardcode'] . "'", "'" . $_GET['whscode'] . "'", "'" . $_GET['objtype'] . "'", "'" . $_SESSION['CodUser'] . "'");
        $SQL = EjecutarSP('sp_ConsultarLotesVerificarSeleccion', $Param);
        $Valid = 1;
        $records = array();
        while ($row = sqlsrv_fetch_array($SQL)) {
            if ($row['CantSolicitada'] != $row['CantTotalSalida']) {
                $Valid = 2;
            }
        }
        $records = array(
            'Result' => $Valid,
        );
        echo json_encode($records);
    } elseif ($type == 18) { //Consultar el LicTradNum de un cliente
        $SQL = Seleccionar("uvw_Sap_tbl_Clientes", "LicTradNum", "CodigoCliente='" . $_GET['CardCode'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'LicTradNum' => $row['LicTradNum'],
        );
        echo json_encode($records);
    } elseif ($type == 19) { //Consultar si todos los items con seriales ya fueron seleccionados
        $Param = array("'" . $_GET['cardcode'] . "'", "'" . $_GET['whscode'] . "'", "'" . $_GET['objtype'] . "'", "'" . $_SESSION['CodUser'] . "'");
        $SQL = EjecutarSP('sp_ConsultarSerialesVerificarSeleccion', $Param);
        $Valid = 1;

        $CantSolicitada = 0;
        $CantTotalSalida = 0;

        $records = array();
        while ($row = sqlsrv_fetch_array($SQL)) {
            if ($row['CantSolicitada'] != $row['CantTotalSalida']) {
                $Valid = 2;

                // SMM, 12/10/2022
                $CantSolicitada = $row['CantSolicitada'];
                $CantTotalSalida = $row['CantTotalSalida'];
            }
        }
        $records = array(
            'Result' => $Valid,
            'CantSolicitada' => $CantSolicitada,
            'CantTotalSalida' => $CantTotalSalida,
        );
        echo json_encode($records);
    } elseif ($type == 20) { //Buscar seriales para seleccionarlos
        $Parametros = array(
            "'" . $_GET['id'] . "'",
            "'" . $_GET['whscode'] . "'",
            // "'" . $_GET['tipotrans'] . "'",
            // "'" . $_GET['cardcode'] . "'",
            // "'" . strtoupper($_GET['buscar']) . "'",
        );
        $SQL = EjecutarSP('sp_ConsultarInventarioSeriales', $Parametros, 0, 2);
        $row = sql_fetch_array($SQL, 2);

        $records = array();
        $records = array(
            'IdSerial' => $row['IdSerial'],
        );
        echo json_encode($records);
    } elseif ($type == 21) { //Buscar dosificacion de articulo en detalle de documentos (COPLA)
        $SQL = Seleccionar("uvw_Sap_tbl_Dosificaciones", "Dosificacion", "ItemCode='" . $_GET['itemcode'] . "' and MetodoAplicacion='" . $_GET['metodo'] . "'");
        $row = sql_fetch_array($SQL);
        if ($row['Dosificacion'] != "") {
            $Value = $row['Dosificacion'];
        } else {
            $Value = 0;
        }
        $records = array();
        $records = array(
            'Dosificacion' => $Value,
        );
        echo json_encode($records);
    } elseif ($type == 22) { //Comprobar si existe un correo electronico
        $records = array();
        if (ValidarEmail(base64_decode($_GET['email']))) {
            $records = array(
                'Result' => 1,
            );
        } else {
            $records = array(
                'Result' => 0,
            );
        }
        echo json_encode($records);
    } elseif ($type == 23) { //Enviar email por SQL
        $records = array();
        $Resultado = EnviarCorreo($_GET['id'], $_GET['objtype'], $_GET['plantilla']);
        $records = array(
            'Estado' => $Resultado,
            'Mensaje' => ($Resultado) ? "Correo enviado exitosamente" : "No se pudo enviar el correo",
            'Title' => ($Resultado) ? "¡Listo!" : "¡Advertencia!",
            'Icon' => ($Resultado) ? "success" : "error",
        );
        echo json_encode($records);
    } elseif ($type == 24) { //Consultar lista total de articulos para el cambio de producto
        $Param = array("'" . $_GET['id'] . "'");
        $SQL = EjecutarSP('sp_ConsultarArticulosTodos', $Param);
        $records = array();
        $j = 0;
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$j] = array(
                'IdArticulo' => $row['IdArticulo'],
                'DescripcionArticulo' => $row['DescripcionArticulo'],
                'NombreBuscarArticulo' => $row['NombreBuscarArticulo'],
            );
            $j++;
        }
        echo json_encode($records);
    } elseif ($type == 25) { //Consultar datos del usuario de SAP
        $SQL = Seleccionar("uvw_Sap_tbl_Empleados", "*", "ID_Empleado='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Cedula' => $row['Cedula'],
            'Telefono' => $row['Celular'],
            'Email' => $row['CorreoElectronico'],
            // SMM, 07/03/2023
            'Sucursal' => ($row['NombreSucursal'] ?? "N/A"),
            'Departamento' => ($row['NombreDepartamento'] ?? "N/A"),
        );
        echo json_encode($records);
    } elseif ($type == 26) { //Obtener nuevo ID de la secuencia de la actividad para el calendario ruta
        $SQL = EjecutarSP('sp_ObtenerIDActividad');
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'NewID' => $row['NewIDActividad'],
        );
        echo json_encode($records);
    } elseif ($type == 27) { //Consultar stock en la entrega o salida de documentos de marketing
        $Param = array("'" . $_GET['cardcode'] . "'", "'" . $_GET['whscode'] . "'", "'" . $_GET['objtype'] . "'", "'" . $_SESSION['CodUser'] . "'");
        $SQL = EjecutarSP('sp_ConsultarStockDocMarketing', $Param);
        $row = sqlsrv_fetch_array($SQL);
        if ($row['CantError'] == 1) {
            $records = array(
                'Estado' => 0,
                'Mensaje' => $row['MsjError'],
                'Title' => "¡Advertencia!",
                'Icon' => "warning",
            );
        } else {
            $records = array(
                'Estado' => 1,
            );
        }
        echo json_encode($records);
    } elseif ($type == 28) { //Consultar si ya existen OT creadas en la creacion de OT en lote
        if ($_GET['doc'] == 1) { //Llamada de servicio
            $SQL = Seleccionar("tbl_CreacionProgramaOrdenesServicio", "*", "Usuario='" . strtolower($_SESSION['User']) . "' and ID_LLamadaServicio <> 0");
        } else { //Orden de venta
            $SQL = Seleccionar("tbl_CreacionProgramaOrdenesServicio", "*", "Usuario='" . strtolower($_SESSION['User']) . "' and ID_OrdenVenta <> 0");
        }

        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        if ($row['ID'] != "") {
            $Result = 1;
        } else {
            $Result = 0;
        }
        $records = array(
            'Result' => $Result,
            'Msg' => ($_GET['doc'] == 1) ? 'Ya hay OT creadas, ¿Desea crearlas de nuevo?' : 'Ya hay Ordenes de venta creadas, ¿Desea crearlas de nuevo?',
        );
        echo json_encode($records);
    } elseif ($type == 29) { //Consultar si hay actividades pendientes por enviar en el programador de rutas al volver a filtrar
        $Param = array(
            8,
            "'" . $_SESSION['CodUser'] . "'",
            "'" . $_GET['idEvento'] . "'",
        );
        $SQL = EjecutarSP("usp_InsertarActividadesRutasToSAP_Core", $Param);
        $row = sqlsrv_fetch_array($SQL);
        if ($row['CantError'] == 0) {
            $records = array(
                'Estado' => 0,
            );
        } else {
            $records = array(
                'Estado' => 1,
                'Mensaje' => $row['MsjError'],
            );

        }
        echo json_encode($records);
    } elseif ($type == 30) { //Consultar los datos en los parametros de la creación de OT
        $SQL = Seleccionar("uvw_tbl_Parametros_Asistentes_Detalle", "*", "IdSerie=" . $_GET['id']);
        $records = array();
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$row['NombreCampo']] = $row['Valor'];
        }
        echo json_encode($records);
    } elseif ($type == 31) { //Consultar el proyecto del cliente
        $SQL = Seleccionar("uvw_Sap_tbl_Clientes", "IdProyecto, DeProyecto", "CodigoCliente='" . $_GET['CardCode'] . "'");
        $row = sqlsrv_fetch_array($SQL);
        $records = array();
        $records = array(
            'IdProyecto' => $row['IdProyecto'],
            'DeProyecto' => $row['DeProyecto'],
        );
        echo json_encode($records);
    } elseif ($type == 32) { //Consultar totales en el asistente de creacion de OT
        $SQL = EjecutarSP('sp_ConsultarTotalesCreacionOT', strtolower($_SESSION['User']));
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'ValOK' => $row['ValOK'],
            'ValNov' => $row['ValNov'],
            'Pend' => $row['Pend'],
            'Creadas' => $row['Creadas'],
            'NoCreadas' => $row['NoCreadas'],
        );
        echo json_encode($records);
    } elseif ($type == 33) { //Consultar totales en el asistente de cambio de producto
        $SQL = EjecutarSP('sp_ConsultarTotalesCambioProducto', strtolower($_SESSION['CodUser']));
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'ValOK' => $row['ValOK'],
            'ValNov' => $row['ValNov'],
            'Pend' => $row['Pend'],
            'Creadas' => $row['Creadas'],
            'NoCreadas' => $row['NoCreadas'],
        );
        echo json_encode($records);
    } elseif ($type == 34) { //Consultar stock en la entrega o salida de documentos de marketing
        $Param = array("'" . $_GET['item'] . "'", "'" . $_GET['id'] . "'");
        $SQL = EjecutarSP('sp_ConsultarRegistrosFrmTemp', $Param);
        $row = sqlsrv_fetch_array($SQL);
        if ($row['CantError'] == 1) {
            $records = array(
                'Estado' => 0,
                'Mensaje' => $row['MsjError'],
                'Title' => "¡Advertencia!",
                'Icon' => "error",
            );
        } else {
            $records = array(
                'Estado' => 1,
            );
        }
        echo json_encode($records);
    } elseif ($type == 35) { //Consultar si ya existen variables en los parametros
        $SQL = Seleccionar("tbl_VariablesGlobales", "*", "NombreVariable='" . $_GET['nomvar'] . "' and Plataforma='" . $_GET['plat'] . "'");

        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        if ($row['ID_Variable'] != "") {
            $Result = 1;
        } else {
            $Result = 0;
        }
        $records = array(
            'Result' => $Result,
        );
        echo json_encode($records);
    } elseif ($type == 36) { //Consultar si ya existen formatos de impresion
        $SQL = Seleccionar("tbl_FormatosSAP", "*", "ID_Objeto='" . $_GET['idObj'] . "' and IdFormato='" . $_GET['idFormato'] . "'");

        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        if (($row['ID'] != "") && ($row['ID'] != $_GET['id'])) {
            $Result = 1;
        } else {
            $Result = 0;
        }
        $records = array(
            'Result' => $Result,
        );
        echo json_encode($records);
    } elseif ($type == 37) { //Traer fecha de vencimiento del cliente por la condicion de pago
        $SQL = Seleccionar("uvw_Sap_tbl_Clientes", "GroupNum", "CodigoCliente='" . $_GET['CardCode'] . "'");
        $row = sqlsrv_fetch_array($SQL);

        $SQL_CP = Seleccionar("uvw_Sap_tbl_CondicionPago", "Dias", "IdCondicionPago='" . $row['GroupNum'] . "'");
        $row_CP = sqlsrv_fetch_array($SQL_CP);

        $fecha = date('Y-m-d');
        $nuevafecha = strtotime('+' . $row_CP['Dias'] . ' day');
        $nuevafecha = date('Y-m-d', $nuevafecha);

        $records = array(
            'FechaVenc' => $nuevafecha,
        );
        echo json_encode($records);
    } elseif ($type == 38) { //Consultar totales en el asistente de cierre de OT
        $Param = array("'" . strtolower($_SESSION['User']) . "'", "'" . $_GET['doctype'] . "'");
        $SQL = EjecutarSP('sp_ConsultarTotalesCierreOT', $Param);
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'ValOK' => $row['ValOK'],
            'ValNov' => $row['ValNov'],
            'Pend' => $row['Pend'],
            'Cerradas' => $row['Cerradas'],
            'NoCerradas' => $row['NoCerradas'],
        );
        echo json_encode($records);
    } elseif ($type == 39) { //Consultar el ID de la sucursal del cliente
        $SQL = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NumeroLinea", "CodigoCliente='" . $_GET['clt'] . "' and NombreSucursal='" . $_GET['suc'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'IdSucursal' => $row['NumeroLinea'],
        );
        echo json_encode($records);
    } elseif ($type == 40) { //Consultar datos del cliente
        $SQL = Seleccionar("uvw_Sap_tbl_Clientes", "*", "CodigoCliente='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'CodigoCliente' => $_GET['id'] ?? '',
            'Telefono' => $row['Telefono'] ?? '',
            // Cargando información en pestaña 'Dirección'
            'DirDestino' => $row['DirDestino'] ?? '',
            'CodPostalDestino' => $row['CodPostalDestino'] ?? '',
            'Ciudad' => $row['CiudadDestino'] ?? '',
            'CodDepartamentoDestino' => $row['CodDepartamentoDestino'] ?? '',
            'DepartamentoDestino' => $row['DepartamentoDestino'] ?? '',
            'PaisDestino' => $row['PaisDestino'] ?? '',
        );
        echo json_encode($records);
    } elseif ($type == 41) { //Consultar porcentaje de etapa oportunidad
        $SQL = Seleccionar("uvw_Sap_tbl_OportunidadesEtapas", "PorcentajeEtapa", "ID_Etapa='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'PorcentajeEtapa' => $row['PorcentajeEtapa'],
        );
        echo json_encode($records);
    } elseif ($type == 42) { //Consultar proyectos
        $SQL = Seleccionar("uvw_Sap_tbl_Proyectos", "IdProyecto", "IdProyecto='" . $_GET['codigo'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'IdProyecto' => $row['IdProyecto'],
        );
        echo json_encode($records);
    } elseif ($type == 43) { //Verificar si en el traslado de inventario hay alguna linea con los almacenes iguales
        $Param = array("'" . $_GET['cardcode'] . "'", "'" . $_SESSION['CodUser'] . "'");
        $SQL = EjecutarSP('sp_tbl_TrasladoInventarioValidarAlmacenesDetalle', $Param);
        $row = sqlsrv_fetch_array($SQL);
        if ($row['CantError'] == 1) {
            $records = array(
                'Estado' => 0,
                'Mensaje' => $row['MsjError'],
                'Title' => "¡Advertencia!",
                'Icon' => "error",
            );
        } else {
            $records = array(
                'Estado' => 1,
            );
        }
        echo json_encode($records);
    }

    // Stiven Muñoz Murillo, 22/12/2021
    elseif ($type == 44) { // Consultar tarjetas de equipo en la llamada de servicio
        $SerialInterno = "'" . $_GET['id'] . "'";

        if ($SerialInterno == "''") {
            $ID_LlamadaServicio = "'" . $_GET['ot'] . "'";

            $SQL = Seleccionar("uvw_Sap_tbl_LlamadasServicios", "IdNumeroSerie", "ID_LlamadaServicio=" . $ID_LlamadaServicio);
            $row = sqlsrv_fetch_array($SQL);

            if (isset($row['IdNumeroSerie'])) {
                $SerialInterno = $row['IdNumeroSerie'];
                $SerialInterno = "'" . $SerialInterno . "'";
            }
        }

        if (isset($ID_LlamadaServicio)) {
            $SQL = Seleccionar("uvw_Sap_tbl_LlamadasServicios", "*", "ID_LlamadaServicio=" . $ID_LlamadaServicio);
            $row = sqlsrv_fetch_array($SQL);

            $NombreContacto = $row['CDU_NombreContacto'] ?? "";
            $TelefonoContacto = $row['CDU_TelefonoContacto'] ?? "";
            $CorreoContacto = $row['CDU_CorreoContacto'] ?? "";

            // SMM, 02/03/2022
            $Kilometros = $row['CDU_Kilometros'] ?? "";
        }

        if ($SerialInterno != "''") {
            $cliente = isset($_GET['clt']) ? "'" . $_GET['clt'] . "'" : "";

            if (isset($_GET['si']) && ($_GET['si'] == 0)) {
                // Ruta nueva, SMM 19/05/2022
                $IdTarjetaEquipo = $SerialInterno;
                if ($cliente == "") {
                    $SQL = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo=$IdTarjetaEquipo");
                } else {
                    $SQL = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo=$IdTarjetaEquipo AND CardCode=$cliente");
                }
            } else {
                // Ruta normal
                if ($cliente == "") {
                    $SQL = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "SerialInterno=$SerialInterno");
                } else {
                    $SQL = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "SerialInterno=$SerialInterno AND CardCode=$cliente");
                }
            }

            $row = sqlsrv_fetch_array($SQL);
            $records = array(
                'SerialInterno' => $row['SerialInterno'],
                'SerialFabricante' => $row['SerialFabricante'],
                'No_Motor' => $row['CDU_No_Motor'],
                'IdArticuloLlamada' => $row['ItemCode'],
                // SMM, 24/01/2022
                'DeArticuloLlamada' => $row['ItemCode'] . " - " . $row['ItemName'],
                // SMM, 24/01/2022
                'CDU_IdMarca' => $row['CDU_IdMarca'],
                'CDU_Marca' => $row['CDU_Marca'],
                'CDU_IdLinea' => $row['CDU_IdLinea'],
                'CDU_Linea' => $row['CDU_Linea'],
                'CDU_Ano' => $row['CDU_Ano'],
                'CDU_Color' => $row['CDU_Color'],
                // SMM, 24/01/2022
                'CDU_Concesionario' => $row['CDU_Concesionario'],
                'CDU_TipoServicio' => $row['CDU_TipoServicio'],
                'CDU_NombreContacto' => $NombreContacto ?? "",
                // SMM, 15/02/2022
                'CDU_TelefonoContacto' => $TelefonoContacto ?? "",
                // SMM, 22/02/2022
                'CDU_CorreoContacto' => $CorreoContacto ?? "",
                // SMM, 22/02/2022
                'CDU_Kilometros' => $Kilometros ?? "", // SMM, 02/03/2022
            );
            echo json_encode($records);
        } else {
            $records = array(
                'SerialInterno' => null,
                'SerialFabricante' => null,
                'No_Motor' => null,
                'IdArticuloLlamada' => null,
                'DeArticuloLlamada' => null,
                'CDU_IdMarca' => null,
                'CDU_Marca' => null,
                'CDU_IdLinea' => null,
                'CDU_Linea' => null,
                'CDU_Ano' => null,
                'CDU_Color' => null,
                'CDU_Concesionario' => null,
                'CDU_TipoServicio' => null,
                'CDU_NombreContacto' => null,
                'CDU_TelefonoContacto' => null,
                'CDU_CorreoContacto' => null,
                'CDU_Kilometros' => null,
            );
            echo json_encode($records);
        }
    }

    // Stiven Muñoz Murillo, 20/01/2022
    elseif ($_GET['type'] == 45) { // Datos del cliente por CodigoCliente
        $CardCode = "'" . $_GET['id'] . "'";
        $SQL = Seleccionar("uvw_Sap_tbl_SociosNegocios", "*", "CodigoCliente=" . $CardCode);
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'Direccion' => $row['Address'],
            'Ciudad' => $row['City'],
            'Celular' => $row['Celular'],
            'Telefono' => $row['Telefono'],
            'Correo' => $row['Email'],
            'IdListaPrecio' => $row['IdListaPrecio'],
            // SMM, 24/02/2022
            'SujetoImpuesto' => $row['SujetoImpuesto'], // SMM, 23/04/2022
        );
        echo json_encode($records);
    }

    // Stiven Muñoz Murillo, 24/01/22
    elseif ($type == 46) { // Buscar articulos en la llamada por código o descripción
        $Consulta = "SELECT TOP(10) ItemCode, ItemName FROM uvw_Sap_tbl_ArticulosLlamadas WHERE ItemCode LIKE '%" . $_GET['id'] . "%' OR ItemName LIKE '%" . $_GET['id'] . "%'";
        $SQL = sqlsrv_query($conexion, $Consulta);

        $j = 0;
        $records = array();
        while ($row = sqlsrv_fetch_array($SQL)) {
            $records[$j] = array(
                'IdArticuloLlamada' => $row['ItemCode'],
                'DeArticuloLlamada' => $row['ItemCode'] . " - " . $row['ItemName'],
                /*
            'DeArticuloLlamada' => $row['ItemCode'] . " - " . $row['ItemName'] .
            " (SERV: " . substr($row['Servicios'], 0, 20) . " - ÁREA: " . substr($row['Areas'], 0, 20) . ")"
             */
            );
            $j++;
        }
        echo json_encode($records);
    }

    // Stiven Muñoz Murillo, 07/02/2022
    elseif ($_GET['type'] == 47) { // Lista de Materiales por ItemCode
        $id = "'" . $_GET['id'] . "'";
        $SQL = Seleccionar("uvw_Sap_tbl_ListaMateriales", "*", "ItemCode=" . $id);
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            'tiempoTarea' => $row['CDU_TiempoTarea'],
        );
        echo json_encode($records);
    }

    // SMM, 10/03/2023
    elseif ($type == 48) { // Consultar linea del cronograma de ordenes de servicio. SMM, 14/01/2023
        $SQL = Seleccionar("uvw_tbl_ProgramacionOrdenesServicio", "*", "ID='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            "SucursalCliente" => $row["IdSucursalCliente"],
            "Estado" => $row["Estado"],
            "Frecuencia" => $row["Frecuencia"],
            "ArticuloLMT" => $row["IdArticuloLMT"],
            "Areas" => $row["Areas"],
            "Servicios" => $row["Servicios"],
            "MetodoAplicacion" => $row["MetodoAplicacion"],
            "Observaciones" => $row["Observaciones"],
        );
        echo json_encode($records);
    }

    // SMM, 10/03/2023
    elseif ($type == 49) { // Consultar una zona de socio de negocio en particular. SMM, 25/02/2023
        $SQL = Seleccionar("uvw_tbl_SociosNegocios_Zonas", "*", "[id_zona_sn]='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            "id_zona_sn" => $row["id_zona_sn"],
            "zona_sn" => $row["zona_sn"],
            "id_socio_negocio" => $row["id_socio_negocio"],
            "id_consecutivo_direccion" => $row["id_consecutivo_direccion"],
            "estado" => $row["estado"],
            "observaciones" => $row["observaciones"],
        );
        echo json_encode($records);
    }

    // SMM, 10/03/2023
    elseif ($type == 50) { // Consultar un punto de control en particular.
        $SQL = Seleccionar("tbl_PuntoControl", "*", "[id_interno]='" . $_GET['id'] . "'");
        $records = array();
        $row = sqlsrv_fetch_array($SQL);
        $records = array(
            "id_punto_control" => $row["id_punto_control"],
            "punto_control" => $row["punto_control"],
            "id_tipo_punto_control" => $row["id_tipo_punto_control"],
            "descripcion_punto_control" => $row["descripcion_punto_control"],
            "id_zona_sn" => $row["id_zona_sn"],
            "id_nivel_infestacion" => $row["id_nivel_infestacion"],
            "instala_tecnico" => $row["instala_tecnico"],
            "fecha_instalacion" => $row["fecha_instalacion"]->format('Y-m-d'),
            "estado" => $row["estado"],
            "umbral_seguridad" => $row["umbral_seguridad"],
            "umbral_critico" => $row["umbral_critico"],
        );
        echo json_encode($records);
    }

    // Después de los condicionales
    // Se cierra la conexión a la BD
    sqlsrv_close($conexion);
}