/**
 * Created by Marko on 04.09.14.
 */
window.addEvent('domready', function () {


    $$('#socialmediaLinks .image_container').each(function (imgContainer) {
        var deleteIcon = new Element('img');
        deleteIcon.setProperties({
            src: 'system/modules/member_rating/assets/images/cancel-circle.png',
            alt: objLang.remove_link,
            title: objLang.remove_link
        });

        deleteIcon.addClass('removeSocialmediaIcon');
        deleteIcon.inject(imgContainer, 'bottom');
        var smIcon = imgContainer.getElements('.socialmediaIcon')[0];

        // ajax request: delete socialmedia link
        deleteIcon.addEvent('click', function () {
            var xhr = new XMLHttpRequest();
            var params = "?isAjaxRequest=true&act=delSocialMediaLink&type=" + smIcon.getProperty('alt');
            xhr.open("GET", document.URL + params, true);

            // Call a function when the state changes.
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    imgContainer.destroy();
                }
            }
            xhr.send(params);
            xhr.send(params);
        });
    });


    var ratingValue = 0;
    $$('#starbox img.star').each(function (el) {
        ratingValue++;
        el.setProperty('onclick', 'rate(this,' + ratingValue + ')');
    });

    // inject hidden-field to bottom
    if (document.id('starbox')) {
        var form = document.id('starbox').getParent('form');
        form.setProperty('onsubmit', 'if(!checkForm(this))return false;');
    }
});


function checkForm(form) {
    if (document.id('ctrl_score').value == '') {
        alert(objLang.err_add_score_between + ' 1 & 5.');
        return false;
    }
    return true;
}


function rate(el, value) {
    document.id('ctrl_score').value = value;
    $$('#starbox img.star').each(function (star) {
        star.removeClass('selected');
    });
    // add class to active star
    el.addClass('selected');
    if (document.id('ctrl_score') != 'undefined') {
        document.id('ctrl_score').value = value;
    }

    if (value > 0) {
        var i = 0;
        $$('#starbox img.star').each(function (star) {
            i++;
            if (i == 0) {
                i = 1;
            }
            if (i == value || i < value) {
                //blue
                star.src = 'system/modules/member_rating/assets/images/star_1.jpg';
            }
            else if (i > value) {
                //grey
                star.src = 'system/modules/member_rating/assets/images/star_2.jpg';
            } else {
                //
            }
        });
    }
}

function toggleVisibility(el, commentId) {
    // ajax request: activate odr deactivate rating
    var xhr = new XMLHttpRequest();
    var params = '?isAjaxRequest=true&act=toggleVisibility&id=' + commentId;
    xhr.open("GET", document.URL + params, true);

    // Call a function when the state changes.
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText != '') {
                el.setAttribute('src', 'system/modules/member_rating/assets/images/' + xhr.responseText + '.png');
            }
        }
    }
    xhr.send(params);
}
