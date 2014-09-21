/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package member_rating
 * @author Marko Cupic 2014
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * @link https://github.com/markocupic/member_rating
 */

// use forEach statement to iterate throw a node list generated with Element.querySelectorAll('css selector');
// https://gist.github.com/DavidBruant/1016007
NodeList.prototype.forEach = Array.prototype.forEach;
HTMLCollection.prototype.forEach = Array.prototype.forEach;


// Plain javascript!!!!
window.addEventListener('load', function () {

    document.querySelectorAll('.mod_member_rating_logged_in_users_profile .socialmediaSection .image_container').forEach(function (imgContainer) {

        var smLink = imgContainer.querySelector('a.socialmediaLink');
        var deleteIcon = imgContainer.querySelector('img.removeSocialmediaIcon');

        // ajax request: delete socialmedia linke
        if (deleteIcon !== null) {
            deleteIcon.addEventListener('click', function () {
                var data = new FormData();
                data.append('REQUEST_TOKEN', ModuleVars.REQUEST_TOKEN);
                data.append('type', smLink.getAttribute('href'));
                var xhr = new XMLHttpRequest();
                var params = "?isAjaxRequest=true&act=delSocialmediaLink";
                xhr.open("POST", document.URL + params, true);

                // Call a function when the state changes.
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        _removeNode(imgContainer);
                    }
                };
                xhr.send(data);
            });
        }
    });

    if (document.querySelector('.starbox')) {
        var ratingValue = 0;
        document.querySelectorAll('.starbox img.star').forEach(function (el) {
            ratingValue++;
            el.setAttribute('onclick', 'rate(this,' + ratingValue + ')');
        });

        var elStarbox = document.querySelector('.starbox');
        if (_getParents(elStarbox, 'form')) {
            _getParents(elStarbox, 'form').setAttribute('onsubmit', 'if(!validateForm(this))return false;');
        }
    }
});

/**
 *
 * @param form
 * @returns {boolean}
 */
function validateForm(form) {
    if (document.querySelector('#ctrl_score').value == '') {
        alert(objLang.err_add_score_between + ' 1 & 5.');
        return false;
    }
    return true;
}

/**
 *
 * @param el
 * @param value
 */
function rate(el, value) {
    document.querySelector('#ctrl_score').value = value;
    document.querySelectorAll('.starbox img.star').forEach(function (star) {
        star.classList.remove('selected');
    });
    // add class to active star
    el.classList.add('selected');
    if (document.querySelector('#ctrl_score') != 'undefined') {
        document.querySelector('#ctrl_score').value = value;
    }

    if (value > 0) {
        var i = 0;
        document.querySelectorAll('.starbox img.star').forEach(function (star) {
            i++;
            if (i == 0) {
                i = 1;
            }
            if (i == value || i < value) {
                //blue
                star.src = ModuleVars.imgDir + '/starrating/star_1.jpg';
            }
            else if (i > value) {
                //grey
                star.src = ModuleVars.imgDir + '/starrating/star_2.jpg';
            } else {
                //
            }
        });
    }
}

/**
 *
 * @param el
 * @param commentId
 */
function toggleVisibility(el, commentId) {
    // ajax request: activate odr deactivate rating
    var xhr = new XMLHttpRequest();
    var params = '?isAjaxRequest=true&act=toggleVisibility&id=' + commentId;
    xhr.open("GET", document.URL + params, true);

    // Call a function when the state changes.
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText != '') {
                el.setAttribute('src', ModuleVars.imgDir + '/' + xhr.responseText + '.png');
            }
        }
    }
    xhr.send(params);
}

/**
 * getParent Element
 * mootools getParent() equivalent
 * @param o
 * @param tag
 * @returns {*}
 * @private
 */
function _getParents(o, tag) {
    while ((o = o.parentNode) && o.tagName) {
        if (o.tagName.toLowerCase() == tag.toLowerCase()) {
            return o;
        }
    }
    return null;
}

/**
 * remove node mootools destroy() equivalent
 * @param elNode
 * @private
 */
function _removeNode(elNode) {
    elNode.parentNode.removeChild(elNode);
}
