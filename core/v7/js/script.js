let sub_menu = document.getElementById("sub-menu");
let nav_toggler = document.getElementById("nav-toggler");
let content_nav_toggler = document.querySelector("#content_nav #profile-nav-toggler");
let content_nav = document.querySelector("#content_nav ul");
console.log(content_nav_toggler);
console.log(content_nav_toggler);

content_nav_toggler.addEventListener("click", (e)=>{
    if(content_nav.classList.contains("h-zero")){
        content_nav.classList.remove("h-zero");
        
        e.target.style.color = "black"
    }else{
        content_nav.classList.add("h-zero");
        e.target.style.color = "#ff6162"
    }
})

nav_toggler.addEventListener("click", () => {
    if (sub_menu.classList.contains("h-0")) {
        sub_menu.classList.remove("h-0");
        sub_menu.classList.add("h-95vh");
        nav_toggler.classList.add("toggled");
        document.body.style.overflow = "hidden";
    } else {
        sub_menu.classList.remove("h-95vh");
        sub_menu.classList.add("h-0");
        nav_toggler.classList.remove("toggled");
        document.body.style.overflow = "";
    }
});
