<div class="row border-bottom">
            <nav class="navbar navbar-static-top white-bg" role="navigation" style="margin-bottom: 0">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-success" href="#"><i class="fa fa-bars"></i> </a>
                </div>
                <ul class="nav navbar-top-links navbar-right">
                   <li>
                        <a href="#">
                            <i class="fa fa-user-circle"></i> <?php echo $_SESSION['User']; ?>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php?msg=<?php echo base64_encode("Cerrando sesión..."); ?>">
                            <i class="fa fa-sign-out"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </nav>
        </div>