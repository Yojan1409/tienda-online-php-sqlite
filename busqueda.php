<?php
    $conexion = new PDO('sqlite:senati_market.db');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $search_term = '';
    $products = [];
    $mensaje_error = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
        $search_term = trim($_POST['search_term'] ?? '');
        try 
        {
            $sql = "SELECT id_producto, nombre, precio, ruta_imagen FROM producto WHERE nombre LIKE :search_term";
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':search_term', "%$search_term%", PDO::PARAM_STR);

            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            $mensaje_error = "Error: " . $e->getMessage();
        }
    }
?>
<html>
<head>
    <title>Busqueda de productos</title>
</head>

<body>
    <form method="post">
        Buscar producto: 
        <input type="text" id="search_term" name="search_term"
        value="<?php echo htmlspecialchars($search_term); ?>">
        <br>
        <button type="submit" name="search">Buscar</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])): ?>

    <h2>Resultados de busqueda</h2>

    <?php
        if (empty($products)):
            echo "<p>No se encontraron resultados</p>";
        else:
    ?>
    
    <form method="post" action="procesar_pedido.php">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Imagen</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['id_producto']); ?></td>
                    <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($product['precio']); ?></td>
                    <td><img src="<?php echo htmlspecialchars($product['ruta_imagen']); ?>" width="50" height="50"></td>
                    <td>
                        <input type="number" name="cantidad[<?php echo $product['id_producto']; ?>]" min="0" value="0">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="procesar">Procesar pedido</button>
    </form>
    
    <?php endif; ?>

    <?php endif; ?>

</body>
</html>