<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thika Baby World</title>
    <link rel="stylesheet" href="style.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.core.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/css/glide.theme.css"
    />
  </head>
  <body>
    <header class="header" id="header">
      <div class="top-nav">
        <div class="container d-flex">
          <p>Order online or call us: +254 719 415 624</p>
          <ul class="d-flex">
            <li><a href="#">About Us</a></li>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>
      </div>

      <div class="navigation">
        <div class="nav-center container d-flex">
          <a href="#" class="logo"><h2>Thika Baby World</h2></a>
          <ul class="nav-list d-flex">
            <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="products.php" class="nav-link">Shop</a></li>
            <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
            <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
            <li class="icons d-flex">
              <a href="login.php" class="icon"
                ><i class='bx bx-user' ></i></a
              >
            
              <a href="cart.php" class="icon"
                ><i class="bx bx-cart"></i>
                <span class="d-flex">0</span>
              </a>
            </li>
          </ul>
          <div class="hamburger">
            <i class="bx bx-menu-alt-left"></i>
          </div>
        </div>
      </div>
      <div class="hero">
        <div class="glide" id="glide_1">
          <div class="glide__track" data-glide-el="track">
            <ul class="glide__slides">
              <li class="glide__slide">
                <div class="center">
                  <div class="left">
                    <span>Home for Kids collection</span>
                    <h1>Welcome to Thika Baby World!</h1>
                    <p>Trending from boy's and girl's style collection</p>
                    <a href="products.php" class="hero-btn">SHOP NOW</a>
                  </div>
                  <div class="right">
                    <img class="img1" src="./images/chicken.jpg" alt="Girl Image">
                  </div>
                </div>
              </li>
              <li class="glide__slide">
                <div class="center">
                  <div class="left">
                    <span>Home For Kids Collection</span>
                    <h1>THIKA BABY WORLD</h1>
                    <p>Trending from boy's and girl's style collection</p>
                    <a href="products.PHP" class="hero-btn">SHOP NOW</a>
                  </div>
                  <div class="right">
                    <img class="img2" src="./images/eatery.jpg" alt="Image 2">
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      </header>
   <!--categories-->
  <section class="section category" id="product">
    <div class="cat-center">
      <div class="cat">
        <img src="./images/cake.jpg" alt="">
        <div>
          <p>BREAKFAST</p>
        </div>
      </div>

      <div class="cat">
        <img src="./images/mshk.jpg" alt="">
        <div>
          <p>LUNCH</p>
        </div>
      </div>

      <div class="cat">
        <img src="./images/omlette.jpg" alt="">
        <div>
          <p>SUPPER</p>
        </div>
      </div>
    </div>
  </section>
  <!-- New Arrivals -->
  <section class="section new-arrival">
    <div class="title">
      <h1>NEW ARRIVALS</h1>
      <p>All the latest picked from designer of our store</p>
    </div>

    <div class="product-center">
      <div class="product-item">
        <div class="overlay">
          <a href="productDetails.html" class="product-thumb">
            <img src="./images/ocen.jpg" alt="" />
          </a>
        </div>
        <div class="product-info">
          <span>GIRLS CLOTHES</span>
          <a href="productDetails.html">Quis Nostrud Exercitation</a>
          <h4>$700</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/corn_bread.jpg" alt="" />
          </a>
          <span class="discount">50%</span>
        </div>

        <div class="product-info">
          <span>BOYS CLOTHES</span>
          <a href="">Sonata White Men’s pullover</a>
          <h4>$800</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/latte.jpg" alt="" />
          </a>
        </div>
        <div class="product-info">
          <span>GIRLS CLOTHES</span>
          <a href="">Concepts Solid girls dress</a>
          <h4>$150</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/pizza.jpg" alt="" />
          </a>
          <span class="discount">50%</span>
        </div>
        <div class="product-info">
          <span>BOYS CLOTHES</span>
          <a href="">Edor do eiusmod tempor</a>
          <h4>$900</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/bugger.jpg" alt="" />
          </a>
        </div>
        <div class="product-info">
          <span>BOYS CLOTHES</span>
          <a href="">Edor do eiusmod tempor</a>
          <h4>$100</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/bread.jpg" alt="" />
          </a>
        </div>
        <div class="product-info">
          <span>Girls CLOTHES</span>
          <a href="">Edor do eiusmod tempor</a>
          <h4>$500</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/ice.jpg" alt="" />
          </a>
          <span class="discount">50%</span>
        </div>
        <div class="product-info">
          <span>MEN'S CLOTHES</span>
          <a href="">Edor do eiusmod tempor</a>
          <h4>$200</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
      <div class="product-item">
        <div class="overlay">
          <a href="" class="product-thumb">
            <img src="./images/corn_bread.jpg" alt="" />
          </a>
        </div>
        <div class="product-info">
          <span>MEN'S CLOTHES</span>
          <a href="">Edor do eiusmod tempor</a>
          <h4>$560</h4>
        </div>
        <ul class="icons">
          <li><i class="bx bx-heart"></i></li>
          <li><i class="bx bx-search"></i></li>
          <li><i class="bx bx-cart"></i></li>
        </ul>
      </div>
    </div>
  </section>
  <!--Promo-->
  
    <!-- Promo -->
    <section class="section banner">
      <div class="left">
        <span class="trend">Trend Design</span>
        <h1>New Collection 2021</h1>
        <p>New Arrival <span class="color">Sale 50% OFF</span> Limited Time Offer</p>
        <a href="#" class="btn btn-1">Discover Now</a>
      </div>
      <div class="right">
        <img src="./images/ocen.jpg" alt="">
      </div>
          </section>
      
       <!-- Contact -->
       <section class="section contact" id="contact">
    <div class="row">
        <div class="col">
            <h2>EXCELLENT SUPPORT</h2>
            <p>We love our customers and they can reach us any time
                of day we will be at your service 24/7</p>
            
            <a href="https://wa.me/254719415624?text=Hello,%20I%20would%20like%20to%20inquire%20about..." 
               class="whatsapp-btn" target="_blank">
                <i class='bx bxl-whatsapp'></i>
                Contact us through WhatsApp
            </a>
        </div>
        
        <div class="col">
            <div id="alert-message" class="alert" style="display: none;"></div>
            
            <form id="contact-form">
                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder="Email Address" required>
                </div>
                <div class="form-group">
                    <input type="text" name="subject" id="subject" placeholder="Subject" required>
                </div>
                <div class="form-group">
                    <textarea name="message" id="message" placeholder="Your Message" required></textarea>
                </div>
                <button type="submit" class="btn-1">Send Message</button>
            </form>
        </div>
    </div>
</section>
    <!-- Footer -->
    <footer class="footer">
      <div class="row">
        <div class="col d-flex">
          <h4>INFORMATION</h4>
          <a href="">About us</a>
          <a href="">Contact Us</a>
          <a href="">Term & Conditions</a>
          <a href="">Shipping Guide</a>
        </div>
       
        <div class="col d-flex">
          <span><i class='bx bxl-facebook-square'></i></span>
          <span><i class='bx bxl-instagram-alt' ></i></span>
          <span><i class='bx bxl-whatsapp' ></i></span>
          <span><i class='bx bxl-gmail' ></i></span>
         
        </div>
      </div>
    </footer>


  <!-- PopUp -->
  <div class="popup hide-popup">
    <div class="popup-content">
      <div class="popup-close">
        <i class='bx bx-x'></i>
      </div>
      <div class="popup-left">
        <div class="popup-img-container">
          <img class="popup-img" src="./images/corn_bread.jpg" alt="popup">
        </div>
      </div>
      <div class="popup-right">
        <div class="right-content">
          <h1>Get Discount <span>50%</span> Off</h1>
          <p>Sign up to our newsletter and save 30% for you next purchase. No spam, we promise!
          </p>
          <form action="#">
            <input type="email" placeholder="Enter your email..." class="popup-form">
            <a href="#">Subscribe</a>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.6.0/glide.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

    <script src="js/slider.js"></script>
    <script src="js/index.js"></script>
  </body>
</html>
