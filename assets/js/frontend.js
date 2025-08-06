// Slick
if (jQuery('.js-carousel-gallery').length) {
    jQuery('.js-carousel-gallery').slick({
        arrows: true,
        asNavFor: '.js-carousel-nav',
        dots: false,
        fade: true,
        nextArrow: '<div class="slick-next mr-15"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"/></svg></div>',
        prevArrow: '<div class="slick-prev ml-15"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M169.4 297.4C156.9 309.9 156.9 330.2 169.4 342.7L361.4 534.7C373.9 547.2 394.2 547.2 406.7 534.7C419.2 522.2 419.2 501.9 406.7 489.4L237.3 320L406.6 150.6C419.1 138.1 419.1 117.8 406.6 105.3C394.1 92.8 373.8 92.8 361.3 105.3L169.3 297.3z"/></svg></div>',
        rows: 0,
        speed: 1000,
        slidesToShow: 1,
        slidesToScroll: 1,
    });
}

if (jQuery('.js-carousel-nav').length) {
    jQuery('.js-carousel-nav').slick({
        arrows: false,
        asNavFor: '.js-carousel-gallery',
        autoplay: true,
        dots: false,
        focusOnSelect: true,
        rows: 0,
        speed: 1000,
        slidesToShow: 3,
        slidesToScroll: 3,
    });
}