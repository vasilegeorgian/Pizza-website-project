<?php

include 'config.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['register'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass'] );
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_user = $conn->prepare("SELECT * FROM `user` WHERE name = ? AND email = ?");
   $select_user->execute([$name, $email]);

   if($select_user->rowCount() > 0){
      $message[] = 'User sau email deja existente!';
   }else{
      if($pass != $cpass){
         $message[] = 'Parola nu este la fel!';
      }else{
         $insert_user = $conn->prepare("INSERT INTO `user`(name, email, password) VALUES(?,?,?)");
         $insert_user->execute([$name, $email, $cpass]);
         $message[] = 'Înregistrat cu succes, vă rugăm să vă logați!';
      }
   }

}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'Cantitate actualizată!';
}

if(isset($_GET['delete_cart_item'])){
   $delete_cart_id = $_GET['delete_cart_item'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$delete_cart_id]);
   header('location:index.php');
}

if(isset($_GET['logout'])){
   session_unset();
   session_destroy();
   header('location:index.php');
}

if(isset($_POST['add_to_cart'])){

   if($user_id == ''){
      $message[] = 'Te rugăm să te loghezi întâi!';
   }else{

      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_STRING);

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND name = ?");
      $select_cart->execute([$user_id, $name]);

      if($select_cart->rowCount() > 0){
         $message[] = 'Deja in cos';
      }else{
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'Adăugat în coș!';
      }

   }

}

if(isset($_POST['order'])){

   if($user_id == ''){
      $message[] = 'Te rugăm să te loghezi întai!';
   }else{
      $name = $_POST['name'];
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $number = $_POST['number'];
      $number = filter_var($number, FILTER_SANITIZE_STRING);
      $address = ''.$_POST['flat'].', '.$_POST['street'].' - '.$_POST['pin_code'];
      $address = filter_var($address, FILTER_SANITIZE_STRING);
      $method = $_POST['method'];
      $method = filter_var($method, FILTER_SANITIZE_STRING);
      $total_price = $_POST['total_price'];
      $total_products = $_POST['total_products'];

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart->execute([$user_id]);

      if($select_cart->rowCount() > 0){
         $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $method, $address, $total_products, $total_price]);
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);
         $message[] = 'Comandă plasată cu succes, vă mulțumim!';
      }else{
         $message[] = 'Coșul tău este gol!';
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
   <title>Pizza Project</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/styles.css">

</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<!-- header section starts  -->

<header class="header">

   <section class="flex">

      <a href="#home" class="logo"><span>P</span>izza.</a>

      <nav class="navbar">
         <a href="#home">Acasă</a>
         <a href="#about">Despre noi</a>
         <a href="#menu">Meniu</a>
         <a href="#order">Comenzi</a>
         <a href="#faq">FAQ</a>
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

      <div id="close-account"><span>inchide</span></div>

      <div class="user">
         <?php
            $select_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
            $select_user->execute([$user_id]);
            if($select_user->rowCount() > 0){
               while($fetch_user = $select_user->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>Bun venit! <span>'.$fetch_user['name'].'</span></p>';
                  echo '<a href="index.php?logout" class="btn">logout</a>';
               }
            }else{
               echo '<p><span>Nu esti logat!</span></p>';
            }
         ?>
      </div>

      <div class="display-orders">
         <?php
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
               }
            }else{
               echo '<p><span>Coșul tău este gol!</span></p>';
            }
         ?>
      </div>

      <div class="flex">

         <form action="user_login.php" method="post">
            <h3>login now</h3>
            <input type="email" name="email" required class="box" placeholder="email@.com" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="parola" maxlength="20">
            <input type="submit" value="login now" name="login" class="btn">
         </form>

         <form action="" method="post">
            <h3>Inregistreaza-te</h3>
            <input type="text" name="name" oninput="this.value = this.value.replace(/\s/g, '')" required class="box" placeholder="username" maxlength="20">
            <input type="email" name="email" required class="box" placeholder="email" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="parola" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="password" name="cpass" required class="box" placeholder="confirmă parola" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="submit" value="Înregistrează-te" name="register" class="btn">
         </form>

      </div>

   </section>

</div>

<div class="my-orders">

   <section>

      <div id="close-orders"><span>Ieșire</span></div>

      <h3 class="title"> Comenzile mele </h3>

      <?php
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){
      ?>
      <div class="box">
         <p> Data : <span><?= $fetch_orders['placed_on']; ?></span> </p>
         <p> Nume : <span><?= $fetch_orders['name']; ?></span> </p>
         <p> Numar : <span><?= $fetch_orders['number']; ?></span> </p>
         <p> Adresa : <span><?= $fetch_orders['address']; ?></span> </p>
         <p> Metoda de plata : <span><?= $fetch_orders['method']; ?></span> </p>
         <p> Total comenzi : <span><?= $fetch_orders['total_products']; ?></span> </p>
         <p> Pret total : <span><?= $fetch_orders['total_price']; ?>LEI</span> </p>
         <p> Status plata: : <span style="color:<?php if($fetch_orders['payment_status'] == 'pending'){ echo 'red'; }else{ echo 'green'; }; ?>"><?= $fetch_orders['payment_status']; ?></span> </p>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">Încă nu ai nici o comandă</p>';
      }
      ?>

   </section>

</div>

<div class="shopping-cart">

   <section>

      <div id="close-cart"><span>închide</span></div>

      <?php
         $grand_total = 0;
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total;
      ?>
      <div class="box">
         <a href="index.php?delete_cart_item=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('Sigur vrei sa stergi?');"></a>
         <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
         <div class="content">
          <p> <?= $fetch_cart['name']; ?> <span>(<?= $fetch_cart['price']; ?> x <?= $fetch_cart['quantity']; ?>)</span></p>
          <form action="" method="post">
             <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
             <input type="number" name="qty" class="qty" min="1" max="99" value="<?= $fetch_cart['quantity']; ?>" onkeypress="if(this.value.length == 2) return false;">
               <button type="submit" class="fas fa-edit" name="update_qty"></button>
          </form>
         </div>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty"><span>Coșul tău este gol</span></p>';
      }
      ?>

      <div class="cart-total"> Total : <span><?= $grand_total; ?>LEI</span></div>

      <a href="#order" class="btn">Comandă acum</a>

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
               <h3>Pepperoni Pizza</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/home-img-2.png" alt="">
            </div>
            <div class="content">
               <h3>Pizza cu ciuperci</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/home-img-3.png" alt="">
            </div>
            <div class="content">
               <h3>Mascarpone și ciuperci</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

      </div>

   </section>

</div>

<!-- about section starts  -->

<section class="about" id="about">

   <h1 class="heading">Despre noi</h1>

   <div class="box-container">

      <div class="box">
         <img src="images/about-1.svg" alt="">
         <h3>Făcută cu iubire</h3>
         <p>Pizza noastră este făcută cu suflet, ingrediente naturale și foc încet, însă secretul din spatele ei, este iubirea.</p>
         <a href="#menu" class="btn">Meniul nostru</a>
      </div>

      <div class="box">
         <img src="images/about-2.svg" alt="">
         <h3>Livrare în maximum 30 de minute</h3>
         <p>Încerăm să livrăm cât mai rapid posibi, cam în 30 de minute.</p>
         <a href="#menu" class="btn">Meniul nostru</a>
      </div>

      <div class="box">
         <img src="images/about-3.svg" alt="">
         <h3>Împarte cu prietenii</h3>
         <p>Așa pizza delicioasă, imposibil să o mănânci de unul singur. Împarte cu prietenii și familia! </p>
         <a href="#menu" class="btn">Meniul nostru</a>
      </div>

   </div>

</section>

<!-- about section ends -->

<!-- menu section starts  -->

<section id="menu" class="menu">

   <h1 class="heading">Meniul nostru</h1>

   <div class="box-container">

      <?php
         $select_products = $conn->prepare("SELECT * FROM `products`");
         $select_products->execute();
         if($select_products->rowCount() > 0){
            while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){
      ?>
      <div class="box">
         <div class="price"><?= $fetch_products['price'] ?>LEI</div>
         <img src="uploaded_img/<?= $fetch_products['image'] ?>" alt="">
         <div class="name"><?= $fetch_products['name'] ?></div>
         <form action="" method="post">
            <input type="hidden" name="pid" value="<?= $fetch_products['id'] ?>">
            <input type="hidden" name="name" value="<?= $fetch_products['name'] ?>">
            <input type="hidden" name="price" value="<?= $fetch_products['price'] ?>">
            <input type="hidden" name="image" value="<?= $fetch_products['image'] ?>">
            <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
            <input type="submit" class="btn" name="add_to_cart" value="add to cart">
         </form>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">Încă nu ai nici un produs!</p>';
      }
      ?>

   </div>

</section>

<!-- menu section ends -->

<!-- order section starts  -->

<section class="order" id="order">

   <h1 class="heading">Meniul nostru</h1>

   <form action="" method="post">

   <div class="display-orders">

   <?php
         $grand_total = 0;
         $cart_item[] = '';
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total;
              $cart_item[] = $fetch_cart['name'].' ( '.$fetch_cart['price'].' x '.$fetch_cart['quantity'].' ) - ';
              $total_products = implode($cart_item);
              echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
            }
         }else{
            echo '<p class="empty"><span>Coșul tău este gol!</span></p>';
         }
      ?>

   </div>

      <div class="grand-total"> Total: <span><?= $grand_total; ?>LEI</span></div>

      <input type="hidden" name="total_products" value="<?= $total_products; ?>">
      <input type="hidden" name="total_price" value="<?= $grand_total; ?>">

      <div class="flex">
         <div class="inputBox">
            <span>Nume:</span>
            <input type="text" name="name" class="box" required placeholder="Nume" maxlength="20">
         </div>
         <div class="inputBox">
            <span>Numar:</span>
            <input type="number" name="number" class="box" required placeholder="Număr de telefon" min="0" max="9999999999" onkeypress="if(this.value.length == 10) return false;">
         </div>
         <div class="inputBox">
            <span>Metoda de plata</span>
            <select name="method" class="box">
               <option value="Plata la livrare">Plată la livrare</option>
               <option value="credit card">Card de credit</option>
               <option value="paytm">GPay</option>
               <option value="paypal">Paypal</option>
            </select>
         </div>
         <div class="inputBox">
            <span>Adresa 1 :</span>
            <input type="text" name="flat" class="box" required placeholder="strada" maxlength="50">
         </div>
         <div class="inputBox">
            <span>Adresa 2:</span>
            <input type="text" name="street" class="box" required placeholder="nr/ap/et" maxlength="50">
         </div>
         <div class="inputBox">
            <span>Cod poștal:</span>
            <input type="number" name="pin_code" class="box" required placeholder="ex 123456" min="0" max="999999" onkeypress="if(this.value.length == 6) return false;">
         </div>
      </div>

      <input type="submit" value="Comandă" class="btn" name="order">

   </form>

</section>

<!-- order section ends -->

<!-- faq section starts  -->

<section class="faq" id="faq">

   <h1 class="heading">FAQ</h1>

   <div class="accordion-container">

      <div class="accordion active">
         <div class="accordion-heading">
            <span>Cum funcționează?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
                Foarte simplu! Îți faci un cont, te loghezi, dai comandă și noi imediat îți livrăm pizza aleasă! (Meniul admin se poate accesa la (https://vasilegeorgiancristinel.hyperphp.com/pizza3/admin_page.php), unde se pot adauga produse noi, updata, sterge si vedea mai multe informatii.)
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>Cât timp durează livrarea?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Maximum 30 de minute. Facem tot posibilul să nu încălcăm legile rutiere și să nu aducem pizza rece.
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>Pot comanda pentru o mega petrecere?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
Desigur, putem oferi si un discount serios, în fucție de cantitatea comandată!
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>Cât de sănătoase sunt produsele?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Folosim doar ingrediente naturale, proaspete, încercând pe cât posibil să putem oferi tuturor cele mai sănătoase produse.
         </p>
      </div>


      <div class="accordion">
         <div class="accordion-heading">
            <span>Aveți reduceri pentru studenți?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Desigur, avem meniuri accesibile în baza legitimației de student/elev.
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
         <h3>Număr telefon</h3>
         <p>0767345004</p>
         
      </div>

      <div class="box">
         <i class="fas fa-map-marker-alt"></i>
         <h3>Adresă</h3>
         <p>Iași, România - 700259</p>
      </div>

      <div class="box">
         <i class="fas fa-clock"></i>
         <h3>Program</h3>
         <p>07:00 - 20:00pm</p>
      </div>

      <div class="box">
         <i class="fas fa-envelope"></i>
         <h3>Email</h3>
         <p>pizza@gmail.com</p>
         
      </div>

   </div>

   <div class="credit">
      &copy; Copyright @ <?= date('Y'); ?> <br> by <a href="https://vasilegeorgiancristinel.hyperphp.com">Vasile Georgian Cristinel</a> | Toate drepturile rezervate!
   </div>

</section>

<!-- footer section ends -->



















<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>
