<?php

    $conexion = new PDO('sqlite:senati_market.db');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $productos_seleccionados = [];
    $mensaje_error = '';
    $mensaje_ok = '';
    $total = 0;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar'])) {
        $cantidades = $_POST['cantidad'];

        $productos_id = array_keys(array_filter($cantidades, function($cantidad) {
            return $cantidad > 0;
        }));

        try {
            $placeholders = implode(',', array_fill(0, count($productos_id), '?'));
            $sql = "SELECT id_producto, nombre, precio, ruta_imagen FROM producto WHERE id_producto IN ($placeholders)";

            $stmt = $conexion->prepare($sql);
            $stmt->execute($productos_id);
            $productos_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos_info as $producto) {
                $id = $producto['id_producto'];
                $productos_seleccionados[] = [
                    'id' => $id,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => $cantidades[$id],
                    'subtotal' => $producto['precio'] * $cantidades[$id]
                ];
                $total += $producto['precio'] * $cantidades[$id];
            }
            
            // Guardar en sesión después de procesar productos
            session_start();
            $_SESSION['productos_seleccionados'] = $productos_seleccionados;
            $_SESSION['total'] = $total;
        }
        catch (PDOException $e) {
            $mensaje_error = "Error: " . $e->getMessage();
        } 
    }


    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_pedido'])) {
        session_start();

        if (!isset($_SESSION['productos_seleccionados']) || empty($_SESSION['productos_seleccionados'])) {
            $mensaje_error = "No hay productos en el pedido";
        }
        else {
            $productos_seleccionados = $_SESSION['productos_seleccionados'];
            $total = $_SESSION['total'];

            $nombre = $_POST['nombre'];
            $correo = $_POST['correo'];
            $direccion = $_POST['direccion'];

            try {

                $conexion->beginTransaction();

                $sql = "INSERT INTO orden_cabecera (nombre, correo, direccion, total, fecha)
                        VALUES (:nombre, :correo, :direccion, :total, datetime('now'))";
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':correo', $correo);
                $stmt->bindParam(':direccion', $direccion);
                $stmt->bindParam(':total', $total);
                $stmt->execute();

                $orden_id = $conexion->lastInsertId();

                $sql = "INSERT INTO orden_detalle (id_orden, id_producto, cantidad, precio, subtotal)
                        VALUES (:id_orden, :id_producto, :cantidad, :precio, :subtotal)";
                $stmt = $conexion->prepare($sql);

                foreach ($productos_seleccionados as $producto) {
                    $stmt->bindParam(':id_orden', $orden_id);
                    $stmt->bindParam(':id_producto', $producto['id']);
                    $stmt->bindParam(':cantidad', $producto['cantidad']);
                    $stmt->bindParam(':precio', $producto['precio']);
                    $stmt->bindParam(':subtotal', $producto['subtotal']);
                    $stmt->execute();
                }

                $conexion->commit();
                $mensaje_ok = "Pedido guardado con el ID " . $orden_id;

                unset($_SESSION['productos_seleccionados']);
                unset($_SESSION['total']);
            }
            catch (PDOException $e) {
                $mensaje_error = "Error: " . $e->getMessage();
            }
    }

    }
?>

<html>
<head>
    <title>Procesar Pedido</title>
</head>
<body>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar']) && !empty($productos_seleccionados)): ?>
    <h2>Productos seleccionados:</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($productos_seleccionados as $producto): ?>
                <tr>
                    <td><?= $producto['id'] ?></td>
                    <td><?= $producto['nombre'] ?></td>
                    <td><?= $producto['precio'] ?></td>
                    <td><?= $producto['cantidad'] ?></td>
                    <td><?= $producto['subtotal'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <strong><?= $total ?></strong>
        <h2>Datos del Cliente</h2>
        <form method="post">
            Nombre: <input type="text" name="nombre" required>
            <br>
            Correo: <input type="email" name="correo" required>
            <br>
            Dirección: <textarea name="direccion" required></textarea>
            <br>
            <button type="submit" name="confirmar_pedido">Confirmar Pedido</button>
        </form>
    <?php endif; ?>
</body>

</html>