<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">
<link rel="stylesheet" href="/public/css/myaccount-posts.css">
<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css">
';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
?>

<div class="container-xl px-4 mt-4">
    <!-- Account page navigation-->

<?PHP  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); ?>


        <hr class="mt-0 mb-4">
    <div class="container flex-grow-1 ">
        <div class="row">
            <div class="col-lg-12">
<div class="container bootstrap snippets bootdey">
    <div class="col-sm-12">
        <div class="panel panel-white post panel-shadow">
            <div class="post-heading">
                <div class="pull-left image">
                    <img src="/public/uploads/user_1.jpg" class="img-circle avatar" alt="user profile image">
                </div>
                <div class="pull-left meta">
                    <div class="title h5">
                        <a href="#"><b>Ryan Haywood</b></a>
                        made a post.
                    </div>
                    <h6 class="text-muted time">1 minute ago</h6>
                </div>
            </div> 
            <div class="post-description"> 
                <p>Bootdey is a gallery of free snippets resources templates and utilities for bootstrap css hmtl js framework. Codes for developers and web designers</p>
                <div class="stats">
                    <a href="#" class="btn btn-default stat-item">
                    <i class="bi bi-hand-thumbs-up-fill"></i> 2
                    </a>
                    <a href="#" class="btn btn-default stat-item">
                    <i class="bi bi-share-fill"></i> 12
                    </a>
                </div>
            </div>
            <div class="post-footer">
                <div class="input-group"> 
                    <input class="form-control" placeholder="Add a comment" type="text">
                    <span class="input-group-addon">
                        <a href="#"><i class="bi bi-pencil-square"></i></a>  
                    </span>
                </div>
                <ul class="comments-list">
                    <li class="comment">
                        <a class="pull-left" href="#">
                            <img class="avatar" src="/public/uploads/user_1.jpg" alt="avatar">
                        </a>
                        <div class="comment-body">
                            <div class="comment-heading">
                                <h4 class="user">Gavino Free</h4>
                                <h5 class="time">5 minutes ago</h5>
                            </div>
                            <p>Sure, oooooooooooooooohhhhhhhhhhhhhhhh</p>
                        </div>
                        <ul class="comments-list">
                            <li class="comment">
                                <a class="pull-left" href="#">
                                    <img class="avatar" src="/public/uploads/user_3.jpg" alt="avatar">
                                </a>
                                <div class="comment-body">
                                    <div class="comment-heading">
                                        <h4 class="user">Ryan Haywood</h4>
                                        <h5 class="time">3 minutes ago</h5>
                                    </div>
                                    <p>Relax my friend</p>
                                </div>
                            </li> 
                            <li class="comment">
                                <a class="pull-left" href="#">
                                    <img class="avatar" src="/public/uploads/user_2.jpg" alt="avatar">
                                </a>
                                <div class="comment-body">
                                    <div class="comment-heading">
                                        <h4 class="user">Gavino Free</h4>
                                        <h5 class="time">3 minutes ago</h5>
                                    </div>
                                    <p>Ok, cool.</p>
                                </div>
                            </li> 
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>
</div>

<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');