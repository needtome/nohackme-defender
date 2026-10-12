if ( typeof dqs === 'function' ) {} else {
    function dqs(val) {
        if ( val == '#' ) return false;
        return document.querySelector(val);
    }
}
if (typeof showMsg == 'undefined') {
    var iMsg = null;

    function showMsg(msg) {
        window.clearTimeout(iMsg);

        if (typeof jQuery == 'undefined') {
            window.setTimeout(showMsg, 33, msg);
            return;
        }

        if (!dqs('#globalMsg')) {
            var newEl = document.createElement("div");
            newEl.setAttribute('id', 'globalMsg');
            dqs('body').appendChild(newEl);
        }

        jQuery('#globalMsg').html(msg + '<div class="closeGM" onclick="hideMsg();">&nbsp;</div>');
        dqs('#globalMsg').style.opacity = 1;
        dqs('#globalMsg').style.display = 'block';
        dqs('#globalMsg').classList.add('animate_show');
        dqs('#globalMsg').classList.remove('animate_hide');

        if (msg.indexOf('data-noclose') < 0) {
            iMsg = window.setTimeout(function() { hideMsg(); }, 5333);
        } else {
            dqs('#globalMsg').classList.add('noclose');
        }
    }

    function hideMsg(fast = false) {
        if (dqs('#globalMsg')) {
            dqs('#globalMsg').classList.add('animate_hide');
            let interval = fast ? 0 : 333;
            window.setTimeout(function() {
                if (dqs('#globalMsg')) {
                    dqs('#globalMsg').style.display = 'none';
                }
            }, interval);
        }
    }
}
if ( typeof showMsgFrom == 'undefined' ) {
    function showMsgFrom(action, val = '', val2 = '', val3 = '', val4 = ''){
        showMsg('<span class="thr" data-noclose="1">&nbsp;</span>');
        jQuery.post('/wp-admin/admin-ajax.php', { action: action, val: val, val2: val2, val3: val3, val4: val4 }, function( data ) {
            if(data!=''){
                data = JSON.parse(data);
                if(data.hasOwnProperty('res') && data.hasOwnProperty('data')){
                    if ( data.hasOwnProperty('redirect_url') ) {
                        window.setTimeout(function() { showMsg('Redirecting...'); location.replace( getLang('url') + data.redirect_url); }, 333);
                    } else {
                        if ( data.res == 'success' ) {
                            if ( data.data != '' ) {
                                showMsg(data.data);
                            }
                        } else if ( data.res == 'error' ) {
                            showMsg(data.data);
                        }
                    }
                }
            }
        } );
    }
}
if ( typeof callFuncFrom == 'undefined' ) {
    function callFuncFrom(call_func, action, val = '', val2 = '', val3 = '', val4 = ''){
        showMsg('<span class="thr" data-noclose="1">&nbsp;</span>');
        jQuery.post('/wp-admin/admin-ajax.php', { action: action, val: val, val2: val2, val3: val3, val4: val4 }, function( data ) {
            if(data!=''){
                data = JSON.parse(data);
                if(data.hasOwnProperty('res') && data.hasOwnProperty('data')){
                    hideMsg();
                    if (typeof window[call_func] === 'function') {
                        window[call_func](data);
                    }
                }
            }
        } );
    }
}
if ( typeof showMsgFromAsk == 'undefined' ) {
    function showMsgFromAsk(text_confirm, text_agree, action, val = '', val2 = '', val3 = ''){
        showMsg(text_confirm + ' <span class="is_a isbtn isbtn_theme_micro isbtn_theme_grey" onclick="showMsgFrom(\'' + action + '\', \'' + val + '\', \'' + val2 + '\', \'' + val3 + '\');">' + text_agree + '</span>');
    }
}
if ( typeof pdx_prepare_ajaxrs2 == 'undefined' ) {
    function pdx_prepare_ajaxrs2() {
        if (!document.getElementById('ajaxrs2')) {
            var newDiv = document.createElement('div');
            newDiv.id = 'ajaxrs2';
            document.body.appendChild(newDiv);
        }
    }
}
if ( typeof showOverlay == 'undefined' ) {
    function showOverlay(title, body, id='', gid='', useJQuery = false, skipCentered = false){
        if(id!=''){
            id=' id="'+id+'"';
        }
        gid += ' inlinebodydiv inlinebodydiv2';
        if ( skipCentered ) {
            gid += ' inlineovery';
        }
        var out='<div id="donottouch2" onclick=" clearars(); ">&nbsp;</div><div class="'+gid+'"'+id+'><div class="inlinetitle">'+title+'</div><div class="closeimg closemeimg" onclick=" clearars(); ">&nbsp;</div><div class="fieldset-wrapper"><div class="cnt"><div class="postblockover"><div class="content"><div class="view-content">';
        out+=body;
        out+='</div></div></div></div></div></div>';

        pdx_prepare_ajaxrs2();
        if ( useJQuery ) {
            jQuery('#ajaxrs2').html(out);
        } else {
            dqs('#ajaxrs2').innerHTML = out;
        }

        if ( !skipCentered ) {
            window.setTimeout(function () {
                let blockHeight = jQuery('#ajaxrs2 .inlinebodydiv2').height();
                let allHeight = window.innerHeight;
                if ( allHeight > blockHeight ) {
                    jQuery('#ajaxrs2 .inlinebodydiv2').css('margin-top', parseInt((allHeight - blockHeight)/2) + 'px');

                    if ( (allHeight - blockHeight) < 27 ) {
                        jQuery('#ajaxrs2 .inlinebodydiv2').addClass('inlineovery');
                    }
                }
            }, 33);
        }
    }
}
if ( typeof showOverlayFrom == 'undefined' ) {
    function showOverlayFrom(action, val = '', val2 = '', val3 = '', val4 = ''){
        pdx_prepare_ajaxrs2();
        jQuery('#ajaxrs2').html('<div id="donottouch" onclick=" clearars(); ">&nbsp;</div>');

        let action_url = '/wp-admin/admin-ajax.php';
        jQuery.post(action_url, { action: action, val: val, val2: val2, val3: val3, val4: val4 }, function( data ) {
            if(data!=''){
                data = JSON.parse(data);
                if(data.hasOwnProperty('res') && data.hasOwnProperty('data')){
                    if ( data.res == 'success' ) {
                        hideMsg();
                        let title = '';
                        if ( data.hasOwnProperty('title') ) {
                            title = data.title;
                        }
                        let skipCentered = false;
                        if ( data.hasOwnProperty('skip_centered') ) {
                            skipCentered = true;
                        }
                        showOverlay(title, data.data, action, 'inlinefixed', true, skipCentered);
                    } else if ( data.res == 'error' ) {
                        showMsg(data.data);
                        clearars();
                    }
                }
            }
        } );
    }
}
if ( typeof showOverlayBlock == 'undefined' ) {
    function showOverlayBlock(sel){
        if ( !jQuery(sel).length ) return;

        showOverlay('', jQuery(sel).html(), '', 'inlinefixed inlineovery');
    }
}
if ( typeof clearars == 'undefined' ) {
    function clearars(){
        dqs('body').classList.remove('show__overlay');
        if ( !dqs('#ajaxrs2') ) return;
        window.setTimeout(function () { dqs('#ajaxrs2').innerHTML = ''; }, 1);
    }
}
if ( typeof copyToCB == 'undefined' ) {
    function copyToCB(val = '') {
        let tempInput = document.createElement('textarea');

        tempInput.style.fontSize = '12pt';
        tempInput.style.position = 'absolute';
        tempInput.style.left = '-9999px';
        tempInput.setAttribute('readonly', '');
        tempInput.value = val;
        dqs('body').appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        tempInput.parentNode.removeChild(tempInput);

        showMsg('Copied to clipboard');
    }
}
if ( typeof copyToClipboard == 'undefined' ) {
    function copyToClipboard(elementId) {
        var textToCopy = document.getElementById(elementId).innerText;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showMsg('Copied to clipboard');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        } else {
            var textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'Copied to clipboard' : 'Failed to copy';
                showMsg(msg);
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }
            document.body.removeChild(textArea);
        }
    }
}
if ( typeof gotoMe == 'undefined' ) {
    function gotoMe(sel, parent = false){
        if ( sel == '' || sel == '#' ) return;

        let block = dqs(sel);
        if ( parent ) {
            block = block.parentNode;
        }

        block.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}
if ( typeof changeTab == 'undefined' ) {
    function changeTab (ind, obj) {
        if ( typeof jQuery == 'undefined' ) {
            window.setTimeout(changeTab, 33, ind, obj);
            return;
        }

        if ( !dqs('.tabPage__title_active') ) return;
        if ( !dqs('.tabDesc_active') ) return;

        var curblock = jQuery(obj).parent().parent();

        if ( curblock.find('.tabPage__title_type_' + ind + '.tabPage__title_active').length ) {
            if ( curWidth < 501 ) {
                if ( curblock.find('.tabPage.tabPage__opened').length ) {
                    curblock.find('.tabPage.tabPage__opened').removeClass('tabPage__opened');
                } else {
                    curblock.find('.tabPage').addClass('tabPage__opened');
                }
            }
            return;
        }
        if ( curblock.find('.tabPage.tabPage__opened').length ) {
            curblock.find('.tabPage.tabPage__opened').removeClass('tabPage__opened');
        }

        curblock.find('[data-tabPage]').attr('data-tabPage', ind);

        curblock.find('.tabPage__title_active').removeClass('tabPage__title_active');
        curblock.find('.tabDesc_active').removeClass('tabDesc_active');

        curblock.find('.tabPage__title_type_' + ind).addClass('tabPage__title_active');
        curblock.find('.tabDesc_type_' + ind).addClass('tabDesc_active');
    }
}
if ( typeof createOrUpdatePopup == 'undefined' ) {
    function createOrUpdatePopup(id, content) {
        let popup = document.getElementById(id);

        if (!popup) {
            popup = document.createElement('div');
            popup.id = id;
            popup.classList.add('popupbg');
            document.body.appendChild(popup);
        }

        popup.innerHTML = content;
        popup.style.display = 'block';
    }
}

if ( typeof getCookie == 'undefined' ) {
    function getCookie(name) {
        var cookie = " " + document.cookie;
        var search = " " + name + "=";
        var setStr = null;
        var offset = 0;
        var end = 0;
        if (cookie.length > 0) {
            offset = cookie.indexOf(search);
            if (offset != -1) {
                offset += search.length;
                end = cookie.indexOf(";", offset)
                if (end == -1) {
                    end = cookie.length;
                }
                setStr = unescape(cookie.substring(offset, end));
            }
        }
        return(setStr);
    }
}
if ( typeof setCookie == 'undefined' ) {
    function setCookie (name, value, expires, path, domain, secure) {
        document.cookie = name + "=" + escape(value) +
              ((expires) ? "; expires=" + expires : "") +
              ((path) ? "; path=" + path : "") +
              ((domain) ? "; domain=" + domain : "") +
              ((secure) ? "; secure" : "");
    }
}
if ( typeof writeMe == 'undefined' ) {
    function writeMe(name, val = ''){
        var out = '';
        if ( val != '' ) {
            out = JSON.stringify(val);
        }
        if (typeof localStorage == 'undefined'){
            setCookie(name, out, "Mon, 01-Jan-"+(parseInt(new Date().getUTCFullYear())+1)+" 00:00:00 GMT", "/");
        }else{
            if( out=='' ){
                localStorage.removeItem(name);
            }else{
                localStorage.setItem(name, out );
            }
        }
    }
}
if ( typeof writeMeTmp == 'undefined' ) {
    function writeMeTmp(name, val = ''){
        var out = '';
        if ( val != '' ) {
            out = JSON.stringify(val);
        }
        if (typeof sessionStorage == 'undefined'){
            let stopdate = new Date((new Date().getTime()) + 86400000);
            stopdate = stopdate.toUTCString();
            setCookie(name, out, stopdate, "/");
        }else{
            if( out=='' ){
                sessionStorage.removeItem(name);
            }else{
                sessionStorage.setItem(name, out );
            }
        }
    }
}
if ( typeof readMe == 'undefined' ) {
    function readMe(name){
        var val='';

        if (typeof localStorage == 'undefined'){
            val=getCookie(name);
        }else{
            val = localStorage.getItem(name);
        }
        if( val!== '' && val !== null ){
            val = JSON.parse(val);
        }

        if( val === '' || val === null ){
            return null;
        }
        return val;
    }
}
if ( typeof readMeTmp == 'undefined' ) {
    function readMeTmp(name){
        var val='';

        if (typeof sessionStorage == 'undefined'){
            val=getCookie(name);
        }else{
            val = sessionStorage.getItem(name);
        }
        if( val!== '' && val !== null ){
            val = JSON.parse(val);
        }

        if( val === '' || val === null ){
            return null;
        }
        return val;
    }
}
if ( typeof pdxnumfoot == 'undefined' ) {
    function pdxnumfoot(count, str1, str2, str3){
        var tmpcount = parseInt(count);
        var tmpiscount = tmpcount;
        if(tmpcount<1){
            return count+' '+str1;
        }
        tmpcount2=-1;
        if(tmpcount>9){
            tmpcount2=tmpcount%100;
        }
        if( tmpcount2>10 && tmpcount2<20 ){
        }else{
            tmpcount2=tmpcount%10;
            switch(tmpcount2){
            case 0: case 5: case 6: case 7: case 8: case 9:
                break;
            case 1:
                return count+' '+str1;
                break;
            case 2: case 3: case 4:
                return count+' '+str3;
                break;
            }
        }
        return pdxformatNumberWithSpaces(count)+' '+str2;
    }
}
if ( typeof pdxformatNumberWithSpaces == 'undefined' ) {
    function pdxformatNumberWithSpaces(number) {
        let numberStr = number.toString();
        let formattedNumber = numberStr.replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return formattedNumber;
    }
}
