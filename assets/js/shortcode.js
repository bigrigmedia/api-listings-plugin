var newsPostsContainer = document.getElementById("api-listings-container");
console.log('newsPostsContainer', newsPostsContainer);

var isHome = newsPostsContainer.getAttribute('data-home') === 'true';
var queryVideo = newsPostsContainer.getAttribute('data-query-video') === 'true';
var sos_number = ''; // Initialize sos_number as empty
var sortOrder = 'newest'; // Default sort order is newest to oldest
var allPosts = []; // Store all fetched posts
var currentDisplayCount = 0; // Track the number of posts currently displayed
var itemsPerBatch = 6; // How many posts to show per batch
//2675
var listingAmount = isHome ? 3 : 32; // Set listing amount based on isHome value
itemsPerBatch = isHome ? 3 : 6; // Set items per batch based on isHome value

var propertyId = api_listings_plugin_settings?.property_id;

// Locate the load more and load less buttons
var loadMoreBTN = document.getElementById("load-more-btn");

// Locate the loading spinner
var loadingSpinner = document.getElementById("loading-spinner");

// Add event listeners for filtering and sorting
document.getElementById('sos_number')?.addEventListener('change', function () {
    sos_number = this.value; // Update sos_number with the selected value 
    fetchPosts(); // Fetch the posts with the new filter
});

document.getElementById('sortOrder')?.addEventListener('change', function () {
    sortOrder = this.value; // Update sortOrder with the selected value (newest or oldest)
    fetchPosts(); // Fetch the posts with the new sort order
});

// Function to show/hide the loading spinner
function showLoading() {
    if (loadingSpinner) {
        loadingSpinner.style.display = 'block';
    }
}
function hideLoading() {
    if (loadingSpinner) {
        loadingSpinner.style.display = 'none';
    }
}

// Function to fetch posts
function fetchPosts() {
    // Show loading animation while fetching
    showLoading();

    // Reset the post count and clear the container
    currentDisplayCount = 0;
    newsPostsContainer.innerHTML = "";

    //Create query param for video
    var videoQuery = queryVideo ? "&video_tour" : "";

    var apiRequest =
        "https://www.legacymhc.com/wp-json/wp/v2/properties?per_page=" + listingAmount + "&parent=" + propertyId + "&_embed&sos_number=" + encodeURIComponent(sos_number) +
        "&listdate=" + encodeURIComponent(sortOrder) + videoQuery;

    var ourRequest = new XMLHttpRequest();
    ourRequest.open("GET", apiRequest);
    ourRequest.onload = function () {
        if (ourRequest.status >= 200 && ourRequest.status < 400) {
            allPosts = JSON.parse(ourRequest.responseText); // Store all fetched posts

            if (allPosts.length === 0 && !isHome) {
                newsPostsContainer.innerHTML = "<p>No listings found.</p>";
                if (loadMoreBTN) {
                    loadMoreBTN.style.display = "none"; // Hide pagination buttons
                }
                hideLoading(); // Hide loading animation
                return;
            }

            // Sort posts by 'sos_number'
            allPosts.sort(function (a, b) {
                const order = ["Community Owned - New", "Community Owned - Used", "Brokered"];
                const indexA = order.indexOf(a.acf.sos_number);
                const indexB = order.indexOf(b.acf.sos_number);
                return indexA - indexB;
            });

            displayPosts(); // Display the first set of posts
            hideLoading(); // Hide loading animation after posts are displayed
            createSlider(allPosts.length);

        } else {
            console.log("Failed to fetch posts");
            hideLoading(); // Hide loading animation if fetch fails
        }
    };

    ourRequest.onerror = function () {
        console.log("Connection Error");
        hideLoading(); // Hide loading animation on error
    };

    ourRequest.send();
}

function createSlider(totalPosts) {
    if (jQuery('.brm-unit-api-half').length == 0) {
        return;
    }

    var slidesToShow = totalPosts >= 2 ? 2 : 1;
    var centerPadding = totalPosts > 2 ? '30px' : '0px';

    if (totalPosts < 2) {
        //Remove 1/3 and 2/3 classes
        jQuery('.unit-api-left').removeClass('md:w-1/3');
        jQuery('.unit-api-right').removeClass('md:w-2/3');
        //Add md:w-1/2 class
        jQuery('.unit-api-left').addClass('md:w-1/2');
        jQuery('.unit-api-right').addClass('md:w-1/2');
    }

    jQuery('.brm-unit-api-half').slick({
        accessibility: true,
        adaptiveHeight: false,
        autoplay: true,
        autoplaySpeed: 5000,
        arrows: false,
        nextArrow: '<div class="next"><img src="/app/themes/sage/resources/assets/images/slick-right.svg" alt="select Next Testimonial"></div>',
        prevArrow: '<div class="prev"><img src="/app/themes/sage/resources/assets/images/slick-left.svg" alt="select Previous Testimonial"></div>',
        dots: true,
        fade: false,
        pauseOnFocus: false,
        pauseOnHover: false,
        speed: 1000,
        slidesToShow: slidesToShow,
        slidesToScroll: 1,
        centerMode: true,
        centerPadding: centerPadding
    })
}

// Function to display posts in batches
function displayPosts() {
    var totalPosts = allPosts.length;

    // Determine how many posts to display in the current batch
    var end = Math.min(currentDisplayCount + itemsPerBatch, totalPosts);

    // Loop through the posts and generate the HTML
    for (var i = currentDisplayCount; i < end; i++) {
        CreateHTML(allPosts[i]);
    }

    currentDisplayCount = end; // Update the count of displayed posts

    // Show or hide the Load More button
    if (currentDisplayCount >= totalPosts) {
        if (loadMoreBTN) {
            loadMoreBTN.style.display = "none";
        }
    } else {
        if (loadMoreBTN) {
            loadMoreBTN.style.display = "block";
        }
    }
}

// Fetch posts on page load
document.addEventListener("DOMContentLoaded", function () {
    fetchPosts(); // Load the first set of posts on page load
});

// Load more button event listener
if (loadMoreBTN) {
    loadMoreBTN.onclick = function () {
        showLoading(); // Show loading when "Load More" is clicked
        setTimeout(function () {
            displayPosts(); // Show more posts when the button is clicked
            hideLoading(); // Hide loading animation after posts are displayed
        }, 500); // Adding delay to simulate loading time
    };
}

// Create HTML for each post
function CreateHTML(postData) {
    var postDate = postData.acf.listdate;
    var formattedDate = new Date(postDate).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "2-digit",
    });

    var price = postData.acf.price_home.toString().replace(/[^0-9.,]/g, '');
    var price_original = postData.acf.original_list_price.toString().replace(/[^0-9.,]/g, '');
    var formatprice = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(price);

    var removeDecimal = formatprice.slice(0, -3);

    var ourHTMLString = "<div id='" + postData.acf.property_purchase_type + "' class=unit-listing >";

    var priceDifference = price_original - price;
    priceDifference = priceDifference / 1000;
    priceDifference = Math.round(priceDifference * 10) / 10;

    if (postData._embedded["wp:featuredmedia"]) {
        ourHTMLString +=
            "<div class=unit-image style='background-image: url(" + postData._embedded["wp:featuredmedia"][0].media_details.sizes.medium.source_url + ")'>";
        if (price_original && price && priceDifference > 0) {
            ourHTMLString += "<div class='reduced-price'>" + "Price Cut: $" + priceDifference + "k</div>";
        }
        ourHTMLString += "</div>";
    } else {
        ourHTMLString += "<div class=unit-image style='background-image: url(https://www.legacymhc.com/app/uploads/2023/12/placeholder.png)'>";
        if (price_original && price && priceDifference > 0) {
            ourHTMLString += "<div class='reduced-price'>" + "Price Cut: $" + priceDifference + "k</div>";
        }
        ourHTMLString += "</div>";
    }

    ourHTMLString += "<div class='unit-data flex-col justify-between'>";
    ourHTMLString += "<a href=/unit-detail?id=" + postData.id + "><strong class='unit-title'>" + postData.title.rendered + "</strong></a>";
    ourHTMLString += "<div>";

    //Start meta-data
    ourHTMLString += "<div class='meta-data'>";
    if (postData.acf.new_construction) {
        ourHTMLString += "<p>" + "New Construction" + "</p>";
    }
    if (postData.acf.construction_type === "400") {
        ourHTMLString += "<p>" + "Manufactured(Single-Section)" + "</p>";
    }
    if (postData.acf.construction_type === "500") {
        ourHTMLString += "<p>" + "Mobile home (built prior to 1976)" + "</p>";
    }

    if (postData.acf.bedrooms_home) {
        ourHTMLString += postData.acf.bedrooms_home + " BD";
    }
    if (postData.acf.bathrooms_home) {
        ourHTMLString += (postData.acf.bedrooms_home ? " | " : "") + postData.acf.bathrooms_home + " BA";
    }


    if (postData.acf.lot_number) {
        if (postData.acf.property_square_footage) {
            ourHTMLString += ((postData.acf.bedrooms_home || postData.acf.bathrooms_home || postData[i].acf.lot_number) ? " | " : "") + postData.acf.property_square_footage + " Sq Ft";
        } else {
            var length = postData.acf.property_length;
            var width = postData.acf.property_width;
            if (length && width && !isNaN(length) && !isNaN(width)) {
                var squareFootage = length.match(/(\d+)/)[0] * width.match(/(\d+)/)[0];
                ourHTMLString += ((postData.acf.bedrooms_home || postData.acf.bathrooms_home || postData.acf.lot_number) ? " | " : "") + squareFootage + " Sq Ft";
            }
        }
    }

    ourHTMLString += "</div>";
    //End meta-data

    ourHTMLString += "<div class=unit-links>";
    ourHTMLString += "<strong class='unit-price'>" + removeDecimal + "</strong>";
    ourHTMLString += "<a href='/unit-detail?id=" + postData.id + "' class='button-api-listing'>" + "Learn More" + "</a>";
    ourHTMLString += "</div>";

    if(postData.acf.lot_number) {
        ourHTMLString += "<div class='listing-number'>Listing # " + postData.acf.lot_number + "/" + postData.acf.listdate + "</div>";
    }

    if (postData.acf.listdate) {
        ourHTMLString += "<div class='list-date'>" + "Listed: " + formattedDate + "</div>";
    }

    ourHTMLString += "<p class='sos-number'>";
    if (postData.acf.sos_number === "Community Owned - New") {
        ourHTMLString += "CO-N";
    } else if (postData.acf.sos_number === "Community Owned - Used") {
        ourHTMLString += "CO-U";
    } else if (postData.acf.sos_number === "Brokered") {
        ourHTMLString += "BRK";
    }
    ourHTMLString += "</p>";
    ourHTMLString += "</div>";
    ourHTMLString += "</div></div>";

    newsPostsContainer.innerHTML += ourHTMLString; // Append the new post to the container
}

