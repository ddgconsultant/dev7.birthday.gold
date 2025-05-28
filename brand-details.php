<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


if (!$account->isadmin()) {
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '
    <div class="container main-content py-12">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-12 mt-5">
                    <img src="/public/images/logo/bg_icon.png">
                    <h1 class="display-1">Coming Soon</h1>
                    <h1 class="mb-4">Our Brand Details Pages Feature</h1>
                    <p class="mb-4">This big dessert isn\'t quite ready to come out of the oven.  Check back soon.</p>
                    <a class="btn btn-primary py-3 px-5" href="/">Go Back To Home</a>
                </div>
            </div>
        </div>
    </div>
        ';
        

    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();
    exit;
    
}
#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$company_id = $qik->decodeId($_GET['cid']);
$company = $app->getcompanydetails($company_id);
$rewards = []; // Fetch reward details here using $company_id
$reviews = []; // Fetch reviews here using $company_id
$social_links = []; // Fetch social links here using $company_id
$company['discount_price'] = $company['discount_price'] ?? 'N/A';
$company['regular_price'] = $company['regular_price'] ?? 'N/A';
$company['shipping_cost'] = $company['shipping_cost'] ?? 'N/A';
$company['stock'] = $company['stock'] ?? false;

$company_images = $app->getcompanyimages($company_id, 5); // Fetch company images here using $company_id
#breakpoint($company_images);
#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
// Handle any form post actions here
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.company-details-header { display: flex; justify-content: space-between; align-items: center; }
.reward-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 20px; }
.social-links-section { margin-top: 20px; }
.tab-pane { margin-top: 20px; }
.review-item { border-bottom: 1px solid #e0e0e0; padding-bottom: 15px; margin-bottom: 15px; }
.rating-stars { color: #f8c51c; }
.input-group-sm { max-width: 100px; }
.swiper-container { margin-bottom: 30px; }
.swiper-slide img { width: 100%; height: auto; }
</style>


    <link href="https://dev6.birthday.gold/v_3.21/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="https://dev6.birthday.gold/v_3.21/vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="https://dev6.birthday.gold/v_3.21/assets/css/theme.css" rel="stylesheet" id="style-default">

    <link href="https://dev6.birthday.gold/v_3.21/assets/css/user.css" rel="stylesheet" id="user-style-default">

';



echo '
<div class="container mt-5 pt-5 main-content">
<div class="row">
';

?>

  <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
      <div class="container" data-layout="container">
       
       
        <div class="content">



          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                  <div class="product-slider" id="galleryTop">
                    <div class="swiper theme-slider position-lg-absolute all-0" data-swiper='{"autoHeight":true,"spaceBetween":5,"loop":true,"loopedSlides":5,"thumb":{"spaceBetween":5,"slidesPerView":5,"loop":true,"freeMode":true,"grabCursor":true,"loopedSlides":5,"centeredSlides":true,"slideToClickedSlide":true,"watchSlidesVisibility":true,"watchSlidesProgress":true,"parent":"#galleryTop"},"slideToClickedSlide":true}'>
                      <div class="swiper-wrapper h-100">
                        <div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1.jpg" alt="" /></div>
                        <div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-2.jpg" alt="" /></div>
                        <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-3.jpg" alt="" /></div>
                        <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-4.jpg" alt="" /></div>
                        <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-5.jpg" alt="" /></div>
                        <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-6.jpg" alt="" /></div>
                      </div>
                      <div class="swiper-nav">
                        <div class="swiper-button-next swiper-button-white"></div>
                        <div class="swiper-button-prev swiper-button-white"></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <h5>Apple MacBook Pro (15" Retina, Touch Bar, 2.2GHz 6-Core Intel Core i7, 16GB RAM, 256GB SSD) - Space Gray (Latest Model)</h5><a class="fs-10 mb-2 d-block" href="#!">Computer &amp; Accessories</a>
                  <div class="fs-11 mb-3 d-inline-block text-decoration-none"><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span><span class="fa fa-star-half-alt text-warning star-icon"></span><span class="ms-1 text-600">(8)</span>
                  </div>
                  <p class="fs-10">Testing conducted by Apple in October 2018 using pre-production 2.9GHz 6‑core Intel Core i9‑based 15-inch MacBook Pro systems with Radeon Pro Vega 20 graphics, and shipping 2.9GHz 6‑core Intel Core i9‑based 15‑inch MacBook Pro systems with Radeon Pro 560X graphics, both configured with 32GB of RAM and 4TB SSD.</p>
                  <h4 class="d-flex align-items-center"><span class="text-warning me-2">$1200</span><span class="me-1 fs-10 text-500">
                      <del class="me-1">$2400</del><strong>-50%</strong></span></h4>
                  <p class="fs-10 mb-1"> <span>Shipping Cost: </span><strong>$50</strong></p>
                  <p class="fs-10">Stock: <strong class="text-success">Available</strong></p>
                  <p class="fs-10 mb-3">Tags: <a class="ms-2" href="#!">Computer,</a><a class="ms-1" href="#!">Mac Book,</a><a class="ms-1" href="#!">Mac Book Pro,</a><a class="ms-1" href="#!">Laptop </a></p>
                  <div class="row">
                    <div class="col-auto pe-0">
                      <div class="input-group input-group-sm" data-quantity="data-quantity">
                        <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="minus">-</button>
                        <input class="form-control text-center input-quantity input-spin-none" type="number" min="0" value="0" aria-label="Amount (to the nearest dollar)" style="max-width: 50px" />
                        <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="plus">+</button>
                      </div>
                    </div>
                    <div class="col-auto px-2 px-md-3"><a class="btn btn-sm btn-primary" href="#!"><span class="fas fa-cart-plus me-sm-2"></span><span class="d-none d-sm-inline-block">Add To Cart</span></a></div>
                    <div class="col-auto px-0"><a class="btn btn-sm btn-outline-danger border border-300" href="#!" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to Wish List"><span class="far fa-heart me-1"></span>282</a></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="mt-4">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                      <li class="nav-item"><a class="nav-link active ps-0" id="description-tab" data-bs-toggle="tab" href="#tab-description" role="tab" aria-controls="tab-description" aria-selected="true">Description</a></li>
                      <li class="nav-item"><a class="nav-link px-2 px-md-3" id="specifications-tab" data-bs-toggle="tab" href="#tab-specifications" role="tab" aria-controls="tab-specifications" aria-selected="false">Specifications</a></li>
                      <li class="nav-item"><a class="nav-link px-2 px-md-3" id="reviews-tab" data-bs-toggle="tab" href="#tab-reviews" role="tab" aria-controls="tab-reviews" aria-selected="false">Reviews</a></li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                      <div class="tab-pane fade show active" id="tab-description" role="tabpanel" aria-labelledby="description-tab">
                        <div class="mt-3">
                          <p>Over the years, Apple has built a reputation for releasing its products with a lot of fanfare – but that didn’t exactly happen for the MacBook Pro 2018. Rather, Apple’s latest pro laptop experienced a subdued launch, in spite of it offering a notable spec upgrade over the 2017 model – along with an improved keyboard. And, as with previous generations the 15-inch MacBook Pro arrives alongside a 13-inch model.</p>
                          <p>Apple still loves the MacBook Pro though, despite the quiet release. This is because, while the iPhone XS and iPad, along with the 12-inch MacBook, are aimed at everyday consumers, the MacBook Pro has always aimed at the creative and professional audience. This new MacBook Pro brings a level of performance (and price) unlike its more consumer-oriented devices. </p>
                          <p>Still, Apple wants mainstream users to buy the MacBook Pro, too. So, if you’re just looking for the most powerful MacBook on the market, you’ll love this new MacBook Pro. Just keep in mind that, while the keyboard has been updated, there are still some issues with it.</p>
                          <p>There’s enough of a difference between the two sizes when it comes to performance to warrant two separate reviews, and here we’ll be looking at how the flagship 15-inch MacBook Pro performs in 2019.</p>
                          <p>It's build quality and design is batter than elit. Numquam excepturi a debitis, sint voluptates, nam odit vel delectus id repellendus vero reprehenderit quidem totam praesentium vitae nesciunt deserunt. Sint, veniam?</p>
                        </div>
                      </div>
                      <div class="tab-pane fade" id="tab-specifications" role="tabpanel" aria-labelledby="specifications-tab">
                        <table class="table fs-10 mt-3">
                          <tbody>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Processor</td>
                              <td>2.3GHz quad-core Intel Core i5,</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Memory</td>
                              <td>8GB of 2133MHz LPDDR3 onboard memory</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Brand Name</td>
                              <td>Apple</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Model</td>
                              <td>Mac Book Pro</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Display</td>
                              <td>13.3-inch (diagonal) LED-backlit display with IPS technology</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Storage</td>
                              <td>512GB SSD</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Graphics</td>
                              <td>Intel Iris Plus Graphics 655</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Weight</td>
                              <td>7.15 pounds</td>
                            </tr>
                            <tr>
                              <td class="bg-100" style="width: 30%;">Finish</td>
                              <td>Silver, Space Gray</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="tab-pane fade" id="tab-reviews" role="tabpanel" aria-labelledby="reviews-tab">
                        <div class="row mt-3">
                          <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="mb-1"><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="ms-3 text-1100 fw-semi-bold">Awesome support, great code 😍</span>
                            </div>
                            <p class="fs-10 mb-2 text-600">By Drik Smith • October 14, 2019</p>
                            <p class="mb-0">You shouldn't need to read a review to see how nice and polished this theme is. So I'll tell you something you won't find in the demo. After the download I had a technical question, emailed the team and got a response right from the team CEO with helpful advice.</p>
                            <hr class="my-4" />
                            <div class="mb-1"><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star-half-alt text-warning star-icon fs-10"></span><span class="ms-3 text-1100 fw-semi-bold">Outstanding Design, Awesome Support</span>
                            </div>
                            <p class="fs-10 mb-2 text-600">By Liane • December 14, 2019</p>
                            <p class="mb-0">This really is an amazing template - from the style to the font - clean layout. SO worth the money! The demo pages show off what Bootstrap 4 can impressively do. Great template!! Support response is FAST and the team is amazing - communication is important.</p>
                          </div>
                          <div class="col-lg-6 ps-lg-5">
                            <form>
                              <h5 class="mb-3">Write your Review</h5>
                              <div class="mb-3">
                                <label class="form-label">Ratting: </label>
                                <div class="d-block" data-rater='{"starSize":32,"step":0.5}'></div>
                              </div>
                              <div class="mb-3">
                                <label class="form-label" for="formGroupNameInput">Name:</label>
                                <input class="form-control" id="formGroupNameInput" type="text" />
                              </div>
                              <div class="mb-3">
                                <label class="form-label" for="formGroupEmailInput">Email:</label>
                                <input class="form-control" id="formGroupEmailInput" type="email" />
                              </div>
                              <div class="mb-3">
                                <label class="form-label" for="formGrouptextareaInput">Review:</label>
                                <textarea class="form-control" id="formGrouptextareaInput" rows="3"></textarea>
                              </div>
                              <button class="btn btn-primary" type="submit">Submit</button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
      
          

        </div>
        <div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog" aria-labelledby="authentication-modal-label" aria-hidden="true">
          <div class="modal-dialog mt-6" role="document">
            <div class="modal-content border-0">
              <div class="modal-header px-5 position-relative modal-shape-header bg-shape">
                <div class="position-relative z-1">
                  <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
                  <p class="fs-10 mb-0 text-white">Please create your free Falcon account</p>
                </div>
                <button class="btn-close position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body py-4 px-5">
                <form>
                  <div class="mb-3">
                    <label class="form-label" for="modal-auth-name">Name</label>
                    <input class="form-control" type="text" autocomplete="on" id="modal-auth-name" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="modal-auth-email">Email address</label>
                    <input class="form-control" type="email" autocomplete="on" id="modal-auth-email" />
                  </div>
                  <div class="row gx-2">
                    <div class="mb-3 col-sm-6">
                      <label class="form-label" for="modal-auth-password">Password</label>
                      <input class="form-control" type="password" autocomplete="on" id="modal-auth-password" />
                    </div>
                    <div class="mb-3 col-sm-6">
                      <label class="form-label" for="modal-auth-confirm-password">Confirm Password</label>
                      <input class="form-control" type="password" autocomplete="on" id="modal-auth-confirm-password" />
                    </div>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="modal-auth-register-checkbox" />
                    <label class="form-label" for="modal-auth-register-checkbox">I accept the <a href="#!">terms </a>and <a class="white-space-nowrap" href="#!">privacy policy</a></label>
                  </div>
                  <div class="mb-3">
                    <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Register</button>
                  </div>
                </form>
                <div class="position-relative mt-5">
                  <hr />
                  <div class="divider-content-center">or register with</div>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-sm-6"><a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#"><span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google</a></div>
                  <div class="col-sm-6"><a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->


    <?PHP

/*



echo '



<div class="d-flex align-items-center">
<div class="toggle-icon-wrapper">

    <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>

</div><a class="navbar-brand" href="../../../index.html">
    <div class="d-flex align-items-center py-3"><img class="me-2" src="../../../assets/img/icons/spot-illustrations/falcon.png" alt="" width="40" /><span class="font-sans-serif text-primary">falcon</span>
    </div>
</a>
</div>








</div>
</div>
</nav>
<div class="content">


';

?>
<div class="card">
<div class="card-body">
    <div class="row">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="product-slider" id="galleryTop">
        <div class="swiper theme-slider position-lg-absolute all-0" 
        data-swiper='{"autoHeight":true,"spaceBetween":5,"loop":true,"loopedSlides":5,"thumb":{"spaceBetween":5,"slidesPerView":5,"loop":true,"freeMode":true,"grabCursor":true,"loopedSlides":5,"centeredSlides":true,
        "slideToClickedSlide":true,"watchSlidesVisibility":true,"watchSlidesProgress":true,"parent":"#galleryTop"},"slideToClickedSlide":true}'>
            <div class="swiper-wrapper h-100">
            <div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1.jpg" alt="" /></div>
            <div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-2.jpg" alt="" /></div>
            <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-3.jpg" alt="" /></div>
            <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-4.jpg" alt="" /></div>
            <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-5.jpg" alt="" /></div>
            <div class="swiper-slide h-100"> <img class="rounded-1 object-fit-cover h-100 w-100" src="https://dev6.birthday.gold/v_3.21/assets/img/products/1-6.jpg" alt="" /></div>
            </div>
            <div class="swiper-nav">
            <div class="swiper-button-next swiper-button-white"></div>
            <div class="swiper-button-prev swiper-button-white"></div>
            </div>
        </div>
        </div>
    </div>
    <?
    echo '
    <div class="col-lg-6">
        <h5>Apple MacBook Pro (15" Retina, Touch Bar, 2.2GHz 6-Core Intel Core i7, 16GB RAM, 256GB SSD) - Space Gray (Latest Model)</h5><a class="fs-10 mb-2 d-block" href="#!">Computer &amp; Accessories</a>
        <div class="fs-11 mb-3 d-inline-block text-decoration-none"><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span>
        <span class="fa fa-star text-warning"></span><span class="fa fa-star-half-alt text-warning star-icon"></span><span class="ms-1 text-600">(8)</span>
        </div>
        <p class="fs-10">Testing conducted by Apple in October 2018 using pre-production 2.9GHz 6‑core Intel Core i9‑based 15-inch MacBook Pro systems with Radeon Pro Vega 20 graphics, 
        and shipping 2.9GHz 6‑core Intel Core i9‑based 15‑inch MacBook Pro systems with Radeon Pro 560X graphics, both configured with 32GB of RAM and 4TB SSD.</p>
        <h4 class="d-flex align-items-center"><span class="text-warning me-2">$1200</span><span class="me-1 fs-10 text-500">
            <del class="me-1">$2400</del><strong>-50%</strong></span></h4>
        <p class="fs-10 mb-1"> <span>Shipping Cost: </span><strong>$50</strong></p>
        <p class="fs-10">Stock: <strong class="text-success">Available</strong></p>
        <p class="fs-10 mb-3">Tags: <a class="ms-2" href="#!">Computer,</a><a class="ms-1" href="#!">Mac Book,</a><a class="ms-1" href="#!">Mac Book Pro,</a><a class="ms-1" href="#!">Laptop </a></p>
        <div class="row">
        <div class="col-auto pe-0">
            <div class="input-group input-group-sm" data-quantity="data-quantity">
            <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="minus">-</button>
            <input class="form-control text-center input-quantity input-spin-none" type="number" min="0" value="0" aria-label="Amount (to the nearest dollar)" style="max-width: 50px" />
            <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="plus">+</button>
            </div>
        </div>
        <div class="col-auto px-2 px-md-3"><a class="btn btn-sm btn-primary" href="#!"><span class="fas fa-cart-plus me-sm-2"></span><span class="d-none d-sm-inline-block">Add To Cart</span></a></div>
        <div class="col-auto px-0"><a class="btn btn-sm btn-outline-danger border border-300" href="#!" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to Wish List"><span class="far fa-heart me-1"></span>282</a></div>
        </div>
    </div>
    </div>
    <div class="row">
    <div class="col-12">
        <div class="mt-4">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active ps-0" id="description-tab" data-bs-toggle="tab" href="#tab-description" role="tab" aria-controls="tab-description" aria-selected="true">Description</a></li>
            <li class="nav-item"><a class="nav-link px-2 px-md-3" id="specifications-tab" data-bs-toggle="tab" href="#tab-specifications" role="tab" aria-controls="tab-specifications" aria-selected="false">Specifications</a></li>
            <li class="nav-item"><a class="nav-link px-2 px-md-3" id="reviews-tab" data-bs-toggle="tab" href="#tab-reviews" role="tab" aria-controls="tab-reviews" aria-selected="false">Reviews</a></li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="tab-description" role="tabpanel" aria-labelledby="description-tab">
            <div class="mt-3">
                <p>Over the years, Apple has built a reputation for releasing its products with a lot of fanfare – but that didn’t exactly happen for the MacBook Pro 2018. Rather, Apple’s 
                latest pro laptop experienced a subdued launch, in spite of it offering a notable spec upgrade over the 2017 model – along with an improved keyboard. And, as with previous generations the 15-inch MacBook Pro arrives alongside a 13-inch model.</p>
                <p>Apple still loves the MacBook Pro though, despite the quiet release. This is because, while the iPhone XS and iPad, along with the 12-inch MacBook, are aimed at everyday 
                consumers, the MacBook Pro has always aimed at the creative and professional audience. This new MacBook Pro brings a level of performance (and price) unlike its more consumer-oriented devices. </p>
                <p>Still, Apple wants mainstream users to buy the MacBook Pro, too. So, if you’re just looking for the most powerful MacBook on the market, you’ll love this new MacBook Pro. 
                Just keep in mind that, while the keyboard has been updated, there are still some issues with it.</p>
                <p>There is enough of a difference between the two sizes when it comes to performance to warrant two separate reviews, and here we’ll be looking at how the flagship 15-inch MacBook Pro performs in 2019.</p>
                <p>It is build quality and design is batter than elit. Numquam excepturi a debitis, sint voluptates, nam odit vel delectus id repellendus vero reprehenderit quidem totam praesentium vitae nesciunt deserunt. Sint, veniam?</p>
            </div>
            </div>
            <div class="tab-pane fade" id="tab-specifications" role="tabpanel" aria-labelledby="specifications-tab">
            <table class="table fs-10 mt-3">
                <tbody>
                <tr>
                    <td class="bg-100" style="width: 30%;">Processor</td>
                    <td>2.3GHz quad-core Intel Core i5,</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Memory</td>
                    <td>8GB of 2133MHz LPDDR3 onboard memory</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Brand Name</td>
                    <td>Apple</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Model</td>
                    <td>Mac Book Pro</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Display</td>
                    <td>13.3-inch (diagonal) LED-backlit display with IPS technology</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Storage</td>
                    <td>512GB SSD</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Graphics</td>
                    <td>Intel Iris Plus Graphics 655</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Weight</td>
                    <td>7.15 pounds</td>
                </tr>
                <tr>
                    <td class="bg-100" style="width: 30%;">Finish</td>
                    <td>Silver, Space Gray</td>
                </tr>
                </tbody>
            </table>
            </div>
            <div class="tab-pane fade" id="tab-reviews" role="tabpanel" aria-labelledby="reviews-tab">
            <div class="row mt-3">
                <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="mb-1"><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="ms-3 text-1100 fw-semi-bold">Awesome support, great code 😍</span>
                </div>
                <p class="fs-10 mb-2 text-600">By Drik Smith • October 14, 2019</p>
                <p class="mb-0">You should not need to read a review to see how nice and polished this theme is. So I will tell you something you wo not find in the demo. After the download I had a technical question, emailed the team and got a response right from the team CEO with helpful advice.</p>
                <hr class="my-4" />
                <div class="mb-1"><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star-half-alt text-warning star-icon fs-10"></span><span class="ms-3 text-1100 fw-semi-bold">Outstanding Design, Awesome Support</span>
                </div>
                <p class="fs-10 mb-2 text-600">By Liane • December 14, 2019</p>
                <p class="mb-0">This really is an amazing template - from the style to the font - clean layout. SO worth the money! The demo pages show off what Bootstrap 4 can impressively do. Great template!! Support response is FAST and the team is amazing - communication is important.</p>
                </div>
                <div class="col-lg-6 ps-lg-5">
                ';
                ?>

                <form>
                    <h5 class="mb-3">Write your Review</h5>
                    <div class="mb-3">
                    <label class="form-label">Ratting: </label>
                    <div class="d-block" data-rater='{"starSize":32,"step":0.5}'></div>
                    </div>
                    <div class="mb-3">
                    <label class="form-label" for="formGroupNameInput">Name:</label>
                    <input class="form-control" id="formGroupNameInput" type="text" />
                    </div>
                    <div class="mb-3">
                    <label class="form-label" for="formGroupEmailInput">Email:</label>
                    <input class="form-control" id="formGroupEmailInput" type="email" />
                    </div>
                    <div class="mb-3">
                    <label class="form-label" for="formGrouptextareaInput">Review:</label>
                    <textarea class="form-control" id="formGrouptextareaInput" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </form>
                <?PHP
                echo '
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
</div>
</div>
<footer class="footer">
<div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
    <div class="col-12 col-sm-auto text-center">
    <p class="mb-0 text-600">Thank you for creating with Falcon <span class="d-none d-sm-inline-block">| </span><br class="d-sm-none" /> 2024 &copy; <a href="https://themewagon.com">Themewagon</a></p>
    </div>
    <div class="col-12 col-sm-auto text-center">
    <p class="mb-0 text-600">v3.21.0</p>
    </div>
</div>
</footer>
</div>
<div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog" aria-labelledby="authentication-modal-label" aria-hidden="true">
<div class="modal-dialog mt-6" role="document">
<div class="modal-content border-0">
    <div class="modal-header px-5 position-relative modal-shape-header bg-shape">
    <div class="position-relative z-1">
        <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
        <p class="fs-10 mb-0 text-white">Please create your free Falcon account</p>
    </div>
    <button class="btn-close position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body py-4 px-5">
    <form>
        <div class="mb-3">
        <label class="form-label" for="modal-auth-name">Name</label>
        <input class="form-control" type="text" autocomplete="on" id="modal-auth-name" />
        </div>
        <div class="mb-3">
        <label class="form-label" for="modal-auth-email">Email address</label>
        <input class="form-control" type="email" autocomplete="on" id="modal-auth-email" />
        </div>
        <div class="row gx-2">
        <div class="mb-3 col-sm-6">
            <label class="form-label" for="modal-auth-password">Password</label>
            <input class="form-control" type="password" autocomplete="on" id="modal-auth-password" />
        </div>
        <div class="mb-3 col-sm-6">
            <label class="form-label" for="modal-auth-confirm-password">Confirm Password</label>
            <input class="form-control" type="password" autocomplete="on" id="modal-auth-confirm-password" />
        </div>
        </div>
        <div class="form-check">
        <input class="form-check-input" type="checkbox" id="modal-auth-register-checkbox" />
        <label class="form-label" for="modal-auth-register-checkbox">I accept the <a href="#!">terms </a>and <a class="white-space-nowrap" href="#!">privacy policy</a></label>
        </div>
        <div class="mb-3">
        <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Register</button>
        </div>
    </form>
    <div class="position-relative mt-5">
        <hr />
        <div class="divider-content-center">or register with</div>
    </div>
    <div class="row g-2 mt-2">
        <div class="col-sm-6"><a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#"><span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google</a></div>
        <div class="col-sm-6"><a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a></div>
    </div>
    </div>
</div>
</div>
</div>
</div>
</main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->




';

/*

<!-- Image Gallery and Company Details -->
<div class="col-lg-3">
<!-- Image Gallery Carousel -->
<div class="product-slider swiper-container" id="galleryTop">
    <div class="swiper-wrapper">';
        foreach ($company_images as $image) {
            echo '
            <div class="swiper-slide">
                <img class="rounded-1 object-fit-cover" src="' . $display->companyimage($company['company_id'] . '/' . $image['company_logo']) . '" alt="Company Image" />
            </div>';
        }
echo '        </div>
    <div class="swiper-nav">
        <div class="swiper-button-next swiper-button-white"></div>
        <div class="swiper-button-prev swiper-button-white"></div>
    </div>
</div>
</div>

            <!-- Company Details -->
            <div class="col-lg-9">
                <h5>' . $company['company_name'] . '</h5>
                <a class="fs-10 mb-2 d-block" href="#!">' . $company['category'] . '</a>
                <div class="fs-11 mb-3 d-inline-block text-decoration-none">
                    <span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span><span class="fa fa-star text-warning"></span>
                    <span class="fa fa-star text-warning"></span>
                    <span class="fa fa-star-half-alt text-warning star-icon"></span><span class="ms-1 text-600">(8)</span>
                </div>
                <p class="fs-10">' . $company['description'] . '</p>
                <h4 class="d-flex align-items-center">
                    <span class="text-warning me-2">$' . $company['discount_price'] . '</span>
                    <span class="me-1 fs-10 text-500">
                        <del class="me-1">$' . $company['regular_price'] . '</del><strong>-50%</strong>
                    </span>
                </h4>
                <p class="fs-10 mb-1"><span>Shipping Cost: </span><strong>$' . $company['shipping_cost'] . '</strong></p>
                <p class="fs-10">Stock: <strong class="text-success">' . ($company['stock'] ? 'Available' : 'Out of Stock') . '</strong></p>
                <p class="fs-10 mb-3">Tags: <a class="ms-2" href="#!">Company,</a><a class="ms-1" href="#!">' . $company['name'] . '</a></p>
                <div class="row">
                    <div class="col-auto pe-0">
                        <div class="input-group input-group-sm" data-quantity="data-quantity">
                            <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="minus">-</button>
                            <input class="form-control text-center input-quantity input-spin-none" type="number" min="0" value="0" aria-label="Amount" style="max-width: 50px" />
                            <button class="btn btn-sm btn-outline-secondary border border-300" data-field="input-quantity" data-type="plus">+</button>
                        </div>
                    </div>
                    <div class="col-auto px-2 px-md-3">
                        <a class="btn btn-sm btn-primary" href="#!">
                            <span class="fas fa-cart-plus me-sm-2"></span><span class="d-none d-sm-inline-block">Add To Cart</span>
                        </a>
                    </div>
                    <div class="col-auto px-0">
                        <a class="btn btn-sm btn-outline-danger border border-300" href="#!" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to Wish List">
                            <span class="far fa-heart me-1"></span>282
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Tabbed section for Description, Specifications, and Reviews -->
<div class="row">
<div class="col-12">
<div class="mt-4">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><a class="nav-link active ps-0" id="description-tab" data-bs-toggle="tab" href="#tab-description" role="tab" aria-controls="tab-description" aria-selected="true">Description</a></li>
        <li class="nav-item"><a class="nav-link px-2 px-md-3" id="specifications-tab" data-bs-toggle="tab" href="#tab-specifications" role="tab" aria-controls="tab-specifications" aria-selected="false">Specifications</a></li>
        <li class="nav-item"><a class="nav-link px-2 px-md-3" id="reviews-tab" data-bs-toggle="tab" href="#tab-reviews" role="tab" aria-controls="tab-reviews" aria-selected="false">Reviews</a></li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="tab-description" role="tabpanel" aria-labelledby="description-tab">
            <div class="mt-3">
                <p>' . $company['long_description'] . '</p>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-specifications" role="tabpanel" aria-labelledby="specifications-tab">
            <table class="table fs-10 mt-3">
                <tbody>
                    <tr><td class="bg-100">Processor</td><td>' . $company['processor'] . '</td></tr>
                    <tr><td class="bg-100">Memory</td><td>' . $company['memory'] . '</td></tr>
                    <tr><td class="bg-100">Brand Name</td><td>' . $company['brand'] . '</td></tr>
                    <tr><td class="bg-100">Model</td><td>' . $company['model'] . '</td></tr>
                    <tr><td class="bg-100">Display</td><td>' . $company['display'] . '</td></tr>
                    <tr><td class="bg-100">Storage</td><td>' . $company['storage'] . '</td></tr>
                    <tr><td class="bg-100">Graphics</td><td>' . $company['graphics'] . '</td></tr>
                    <tr><td class="bg-100">Weight</td><td>' . $company['weight'] . '</td></tr>
                    <tr><td class="bg-100">Finish</td><td>' . $company['finish'] . '</td></tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="tab-reviews" role="tabpanel" aria-labelledby="reviews-tab">
            <div class="row mt-3">';
                foreach ($reviews as $review) {
                    echo '
                    <div class="col-lg-6 mb-4">
                        <div class="review-item">
                            <div class="mb-1">
                                <span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star text-warning fs-10"></span><span class="fa fa-star-half-alt text-warning fs-10"></span>
                                <span class="ms-3 text-1100 fw-semi-bold">' . $review['title'] . '</span>
                            </div>
                            <p class="fs-10 mb-2 text-600">By ' . $review['author'] . ' • ' . $review['date'] . '</p>
                            <p>' . $review['comment'] . '</p>
                        </div>
                    </div>';
                }
echo '                </div>
        </div>
    </div>
</div>
</div>
</div>
</div>';

*/
?>





    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="https://dev6.birthday.gold/v_3.21/vendors/popper/popper.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/anchorjs/anchor.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/is/is.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/swiper/swiper-bundle.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/rater-js/index.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/fontawesome/all.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/lodash/lodash.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
    <script src="https://dev6.birthday.gold/v_3.21/vendors/list.js/list.min.js"></script>
    <script src="https://dev6.birthday.gold/v_3.21/assets/js/theme.js"></script>



<!-- Swiper JS 
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
var swiper = new Swiper('.swiper-container', {
spaceBetween: 10,
loop: true,
navigation: {
nextEl: '.swiper-button-next',
prevEl: '.swiper-button-prev',
},
});
</script>
-->
<?PHP
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
