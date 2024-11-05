<?php

include 'config.php';

session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
};

if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_STRING);
    $pass = sha1($_POST['pass']);
    $pass = filter_var($pass, FILTER_SANITIZE_STRING);
    $cpass = sha1($_POST['cpass']);
    $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

    $select_user = $conn->prepare("SELECT * FROM `user` WHERE name = ? AND email = ?");
    $select_user->execute([$name, $email]);

    if ($select_user->rowCount() > 0) {
        $message[] = 'Usuário ou Email já cadastrado!';
    } else {
        if ($pass != $cpass) {
            $message[] = 'Senhas não coincidem!';
        } else {
            $insert_user = $conn->prepare("INSERT INTO `user`(name, email, password) VALUES(?,?,?)");
            $insert_user->execute([$name, $email, $cpass]);
            $message[] = 'Registrado com sucesso, por favor faça o login!';
        }
    }
}

if (isset($_POST['update_qty'])) {
    $cart_id = $_POST['cart_id'];
    $qty = $_POST['qty'];
    $qty = filter_var($qty, FILTER_SANITIZE_STRING);
    $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
    $update_qty->execute([$qty, $cart_id]);
    $message[] = 'Quantidade atualizada!';
}

if (isset($_GET['delete_cart_item'])) {
    $delete_cart_id = $_GET['delete_cart_item'];
    $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
    $delete_cart_item->execute([$delete_cart_id]);
    header('location:index.php');
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('location:index.php');
}

if (isset($_POST['add_to_cart'])) {

    if ($user_id == '') {
        $message[] = 'Por favor faça o login antes!';
    } else {

        $pid = $_POST['pid'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $image = $_POST['image'];
        $qty = $_POST['qty'];
        $qty = filter_var($qty, FILTER_SANITIZE_STRING);

        $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND name = ?");
        $select_cart->execute([$user_id, $name]);

        if ($select_cart->rowCount() > 0) {
            $message[] = 'Já adicionado ao carrinho';
        } else {
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
            $message[] = 'Adicionado ao carrinho!';
        }
    }
}

if (isset($_POST['order'])) {

    if ($user_id == '') {
        $message[] = 'Por favor faça o login antes!';
    } else {
        $name = $_POST['name'];
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $number = $_POST['number'];
        $number = filter_var($number, FILTER_SANITIZE_STRING);
        $address = $_POST['flat'] . ', ' . $_POST['street'] . ' - ' . $_POST['pin_code'];
        $address = filter_var($address, FILTER_SANITIZE_STRING);
        $method = $_POST['method'];
        $method = filter_var($method, FILTER_SANITIZE_STRING);
        $total_price = $_POST['total_price'];
        $total_products = $_POST['total_products'];

        $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
        $select_cart->execute([$user_id]);

        if ($select_cart->rowCount() > 0) {
            $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?)");
            $insert_order->execute([$user_id, $name, $number, $method, $address, $total_products, $total_price]);
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);
            $message[] = 'Pedido realizado com sucesso!';
        } else {
            $message[] = 'Seu carrinho está vazio!';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizzaria Delícia</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <?php
    if (isset($message)) {
        foreach ($message as $message) {
            echo '
         <div class="message">
            <span>' . $message . '</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
        }
    }
    ?>

    <!-- header section starts  -->

    <header class="header">

        <section class="flex">

            <a href="#home" class="logo"><span>Pizzaria</span>Delícia</a>

            <nav class="navbar">
                <a href="#home">Inicio</a>
                <a href="#about">Sobre</a>
                <a href="#menu">Menu</a>
                <a href="#order">Pedido</a>
                <a href="#faq">FAQ </a>
            </nav>

            <div class="icons">
                <div id="menu-btn" class="fas fa-bars"></div>
                <div id="user-btn" class="fas fa-user"></div>
                <div id="order-btn" class="fas fa-box"></div>
                <?php
                $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                $count_cart_items->execute([$user_id]);
                $total_cart_items = $count_cart_items->rowCount();
                ?>
                <div id="cart-btn" class="fas fa-shopping-cart"><span>(<?= $total_cart_items; ?>)</span></div>
            </div>

        </section>

    </header>

    <!-- header section ends -->

    <div class="user-account">

        <section>

            <div id="close-account"><span>Fechar</span></div>

            <div class="user">
                <?php
                $select_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
                $select_user->execute([$user_id]);
                if ($select_user->rowCount() > 0) {
                    while ($fetch_user = $select_user->fetch(PDO::FETCH_ASSOC)) {
                        echo '<p>Bem Vindo(a) ! <span>' . $fetch_user['name'] . '</span></p>';
                        echo '<a href="index.php?logout" class="btn">logout</a>';
                    }
                } else {
                    echo '<p><span>Você não está logado agora!</span></p>';
                }
                ?>
            </div>

            <div class="display-orders">
                <?php
                $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                $select_cart->execute([$user_id]);
                if ($select_cart->rowCount() > 0) {
                    while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                        echo '<p>' . $fetch_cart['name'] . ' <span>(' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ')</span></p>';
                    }
                } else {
                    echo '<p><span>Seu carrinho está vazio!</span></p>';
                }
                ?>
            </div>

            <div class="flex">

                <form action="user_login.php" method="post">
                    <h3>Logue agora</h3>
                    <input type="email" name="email" required class="box" placeholder="Digite seu Email" maxlength="50">
                    <input type="password" name="pass" required class="box" placeholder="Digite sua Senha"
                        maxlength="20">
                    <input type="submit" value="Logue agora" name="login" class="btn">
                </form>

                <form action="" method="post">
                    <h3>Registre agora</h3>
                    <input type="text" name="name" oninput="this.value = this.value.replace(/\s/g, '')" required
                        class="box" placeholder="Digite seu usuario" maxlength="20">
                    <input type="email" name="email" required class="box" placeholder="Digite seu Email" maxlength="50">
                    <input type="password" name="pass" required class="box" placeholder="Digite sua Senha"
                        maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
                    <input type="password" name="cpass" required class="box" placeholder="Confirme sua Senha"
                        maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
                    <input type="submit" value="Registre agora" name="register" class="btn">
                </form>

            </div>

        </section>

    </div>

    <div class="my-orders">

        <section>

            <div id="close-orders"><span>Fechar</span></div>

            <h3 class="title"> Meus Pedidos </h3>

            <?php
            $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
            $select_orders->execute([$user_id]);
            if ($select_orders->rowCount() > 0) {
                while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <div class="box">
                <p> Pedido dia: <span><?= $fetch_orders['placed_on']; ?></span> </p>
                <p> Nome : <span><?= $fetch_orders['name']; ?></span> </p>
                <p> Número : <span><?= $fetch_orders['number']; ?></span> </p>
                <p> Endereço : <span><?= $fetch_orders['address']; ?></span> </p>
                <p> Metodo de Pagamento : <span><?= $fetch_orders['method']; ?></span> </p>
                <p> Seu Pedido: <span><?= $fetch_orders['total_products']; ?></span> </p>
                <p> Preço Total: <span>$<?= $fetch_orders['total_price']; ?>/-</span> </p>
                <p> Status do Pagamento: <span
                        style="color:<?php if ($fetch_orders['payment_status'] == 'Pendente') {
                                                                        echo 'red';
                                                                    } else {
                                                                        echo 'green';
                                                                    }; ?>"><?= $fetch_orders['payment_status']; ?></span>
                </p>
            </div>
            <?php
                }
            } else {
                echo '<p class="empty">Nenhum Pedido Feito!</p>';
            }
            ?>

        </section>

    </div>

    <div class="shopping-cart">

        <section>

            <div id="close-cart"><span>Fechar</span></div>

            <?php
            $grand_total = 0;
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
                    $grand_total += $sub_total;
            ?>
            <div class="box">
                <a href="index.php?delete_cart_item=<?= $fetch_cart['id']; ?>" class="fas fa-times"
                    onclick="return confirm('delete this cart item?');"></a>
                <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
                <div class="content">
                    <p> <?= $fetch_cart['name']; ?> <span>(<?= $fetch_cart['price']; ?> x
                            <?= $fetch_cart['quantity']; ?>)</span></p>
                    <form action="" method="post">
                        <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                        <input type="number" name="qty" class="qty" min="1" max="99"
                            value="<?= $fetch_cart['quantity']; ?>"
                            onkeypress="if(this.value.length == 2) return false;">
                        <button type="submit" class="fas fa-edit" name="update_qty"></button>
                    </form>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<p class="empty"><span>Seu carrinho está vazio!</span></p>';
            }
            ?>

            <div class="cart-total"> Total Geral: : <span>$<?= $grand_total; ?>/-</span></div>

            <a href="#order" class="btn">Peça Agora</a>

        </section>

    </div>

    <div class="home-bg">

        <section class="home" id="home">

            <div class="slide-container">

                <div class="slide active">
                    <div class="image">
                        <img src="images/home-img-1.png" alt="">
                    </div>
                    <div class="content">
                        <h3>Pizza de Calabresa</h3>
                        <div class="fas fa-angle-left" onclick="prev()"></div>
                        <div class="fas fa-angle-right" onclick="next()"></div>
                    </div>
                </div>

                <div class="slide">
                    <div class="image">
                        <img src="images/home-img-2.png" alt="">
                    </div>
                    <div class="content">
                        <h3>Pizza de Cogumelos</h3>
                        <div class="fas fa-angle-left" onclick="prev()"></div>
                        <div class="fas fa-angle-right" onclick="next()"></div>
                    </div>
                </div>

            </div>

        </section>
        0
    </div>

    <!-- about section starts  -->

    <section class="about" id="about">

        <h1 class="heading">Sobre Nós</h1>

        <div class="box-container">
            <div class="box">
                <img src="images/about-1.svg" alt="Sobre Nós">
                <p>Na Pizzaria Delícia, cada pizza é uma expressão de amor e dedicação. Prometemos entregar sua pizza em
                    30 minutos, quentinha e saborosa. Acreditamos que uma boa pizza é um momento para ser compartilhado
                    com quem amamos, proporcionando uma experiência gastronômica e fortalecendo laços ao redor da mesa.
                </p>
                <br>
                <a href="#menu" class="btn">Nosso Menu</a>
            </div>
        </div>

    </section>

    <!-- about section ends -->


    <!-- menu section starts  -->

    <section id="menu" class="menu">

        <h1 class="heading">Nosso Menu</h1>

        <div class="box-container">

            <?php
            $select_products = $conn->prepare("SELECT * FROM `products`");
            $select_products->execute();
            if ($select_products->rowCount() > 0) {
                while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <div class="box">
                <div class="price">R$<?= $fetch_products['price'] ?></div>
                <img src="uploaded_img/<?= $fetch_products['image'] ?>" alt="">
                <div class="name"><?= $fetch_products['name'] ?></div>
                <form action="" method="post">
                    <input type="hidden" name="pid" value="<?= $fetch_products['id'] ?>">
                    <input type="hidden" name="name" value="<?= $fetch_products['name'] ?>">
                    <input type="hidden" name="price" value="<?= $fetch_products['price'] ?>">
                    <input type="hidden" name="image" value="<?= $fetch_products['image'] ?>">
                    <input type="number" name="qty" class="qty" min="1" max="99"
                        onkeypress="if(this.value.length == 2) return false;" value="1">
                    <input type="submit" class="btn" name="add_to_cart" value="add to cart">
                </form>
            </div>
            <?php
                }
            } else {
                echo '<p class="empty">Nenhum produto adicionado!</p>';
            }
            ?>

        </div>

    </section>

    <!-- menu section ends -->

    <!-- order section starts  -->

    <section class="order" id="order">

        <h1 class="heading">Peça agora</h1>

        <form action="" method="post">

            <div class="display-orders">

                <?php
                $grand_total = 0;
                $cart_item[] = '';
                $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                $select_cart->execute([$user_id]);
                if ($select_cart->rowCount() > 0) {
                    while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                        $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
                        $grand_total += $sub_total;
                        $cart_item[] = $fetch_cart['name'] . ' ( ' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ' ) - ';
                        $total_products = implode($cart_item);
                        echo '<p>' . $fetch_cart['name'] . ' <span>(' . $fetch_cart['price'] . ' x ' . $fetch_cart['quantity'] . ')</span></p>';
                    }
                } else {
                    echo '<p class="empty"><span>Seu carrinho está vazio!</span></p>';
                }
                ?>

            </div>

            <div class="grand-total"> Total Geral : <span>R$<?= $grand_total; ?></span></div>

            <input type="hidden" name="total_products" value="<?= $total_products; ?>">
            <input type="hidden" name="total_price" value="<?= $grand_total; ?>">

            <div class="flex">
                <div class="inputBox">
                    <span>Seu nome :</span>
                    <input type="text" name="name" class="box" required placeholder="Digite seu Nome" maxlength="20">
                </div>
                <div class="inputBox">
                    <span>Seu Número :</span>
                    <input type="number" name="number" class="box" required placeholder="Digite seu número" min="0"
                        max="9999999999" onkeypress="if(this.value.length == 10) return false;">
                </div>
                <div class="inputBox">
                    <span>Metódo de Pagamento</span>
                    <select name="method" class="box">
                        <option value="Dinheiro na entrega">Dinheiro na entrega</option>
                        <option value="Pix">Pix</option>
                    </select>
                </div>
                <div class="inputBox">
                    <span>Rua :</span>
                    <input type="text" name="flat" class="box" required placeholder="Ex: Rua Colorado " maxlength="50">
                </div>
                <div class="inputBox">
                    <span>Bairro :</span>
                    <input type="text" name="street" class="box" required placeholder="Ex: Santa Paula" maxlength="50">
                </div>
                <div class="inputBox">
                    <span>Número da casa :</span>
                    <input type="number" name="pin_code" class="box" required placeholder="Ex:84575-857 " min="0"
                        max="99999999" onkeypress="if(this.value.length == 8) return false;">
                </div>
            </div>

            <input type="submit" value="Peça agora" class="btn" name="order">

        </form>

    </section>

    <!-- order section ends -->

    <!-- faq section starts  -->

    <section class="faq" id="faq">

        <h1 class="heading">FAQ</h1>

        <div class="accordion-container">

            <div class="accordion active">
                <div class="accordion-heading">
                    <span>Como funciona?</span>
                    <i class="fas fa-angle-down"></i>
                </div>
                <p class="accrodion-content">
                    Na Pizzaria Delícia, o processo é simples: você escolhe seus sabores favoritos do
                    nosso menu diversificado, faz seu pedido online ou por telefone e aguarda nossa entrega
                    rápida e
                    eficiente. Em pouco tempo, você estará desfrutando de uma pizza fresca e saborosa no
                    conforto da sua
                    casa.
                </p>
            </div>

            <div class="accordion">
                <div class="accordion-heading">
                    <span>Quanto tempo leva para a entrega?</span>
                    <i class="fas fa-angle-down"></i>
                </div>
                <p class="accrodion-content">
                    Nosso compromisso é entregar sua pizza em até 30 minutos após a confirmação do pedido.
                    Nossa equipe
                    trabalha diligentemente para garantir que sua refeição chegue quentinha e deliciosa, sem
                    demoras
                    desnecessárias.
                </p>
            </div>

            <div class="accordion">
                <div class="accordion-heading">
                    <span>Posso encomendar para festas?</span>
                    <i class="fas fa-angle-down"></i>
                </div>
                <p class="accrodion-content">
                    Absolutamente! Seja uma reunião casual entre amigos ou uma grande festa, estamos aqui
                    para atender
                    às suas necessidades. Oferecemos opções de catering personalizado para tornar seu evento
                    ainda mais
                    especial. Entre em contato conosco para discutir suas preferências e garantir que sua
                    festa seja um
                    sucesso gastronômico.
                </p>
            </div>

            <div class="accordion">
                <div class="accordion-heading">
                    <span>Quanta proteina tem?</span>
                    <i class="fas fa-angle-down"></i>
                </div>
                <p class="accrodion-content">
                    Nossas pizzas são uma excelente fonte de proteína, especialmente se você optar por
                    sabores que
                    contenham ingredientes ricos em proteína, como queijo, carne e frango. Cada pizza varia
                    em sua
                    composição nutricional, mas fique tranquilo, pois garantimos que todas sejam preparadas
                    com
                    ingredientes frescos e de alta qualidade.
                </p>
            </div>


            <div class="accordion">
                <div class="accordion-heading">
                    <span>É cozido com óleo?</span>
                    <i class="fas fa-angle-down"></i>
                </div>
                <p class="accrodion-content">
                    Na Pizzaria Delícia, priorizamos a qualidade e o sabor em cada pizza que preparamos.
                    Nossas pizzas
                    são assadas em fornos especiais, sem a necessidade de óleo adicional. Utilizamos métodos
                    de
                    cozimento que ressaltam os sabores naturais dos ingredientes, garantindo uma experiência
                    gastronômica saudável e deliciosa.
                </p>
            </div>

        </div>

    </section>

    <!-- faq section ends -->

    <!-- footer section starts  -->

    <section class="footer">

        <div class="box-container">

            <div class="box">
                <i class="fas fa-phone"></i>
                <h3>Telefone</h3>
                <p>+55 9999-9999</p>
                <p>+55 99 99999-9999</p>
            </div>

            <div class="box">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Endereço</h3>
                <p>R. Colorado, Santa Paula, 84575-857</p>
            </div>

            <div class="box">
                <i class="fas fa-clock"></i>
                <h3>Horários</h3>
                <p>Das 18:00hrs as 23:00hrs</p>
                <p>Não abrimos as segundas</p>
            </div>

            <div class="box">
                <i class="fas fa-envelope"></i>
                <h3>E-mails</h3>
                <p>pizzariadelicia@gmail.com</p>
                <p>pizzadelicia@gmail.com</p>
            </div>

        </div>

        <div class="credit">
            &copy; copyright @ UTFPR <?= date('Y'); ?> por <span>João Pedro de Oliveira Moraes</span> | Todos os
            direitos reservados!
        </div>

    </section>

    <!-- footer section ends -->

    <!-- custom js file link  -->
    <script src="js/script.js"></script>

</body>

</html>