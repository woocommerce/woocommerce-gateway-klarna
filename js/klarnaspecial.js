var klarnaSpecialPaymentLang = '';
var klarnaSpecialPaymentEid = 0;
var klarnaPClassId = '';
var link_text_specialpayment = '';
function InitKlarnaSpecialPaymentElements(obj, eid, lang, linktext, pcid)
{
    if (document.getElementById(obj) == null) {
        return;
    }
    klarnaSpecialPaymentLang = lang;
    klarnaSpecialPaymentEid = eid;
    klarnaPClassId = pcid;
    link_text_specialpayment = linktext;
    var link_text_closebutton = 'St&auml;ng';
    var klarnawidth = '500px';
    var klarnahight = '300px';

    switch(lang)
    {
        case 'se':
        case 'swe':
            /*link_text_specialpayment = 'L&auml;s mer';*/
            link_text_closebutton = 'St&auml;ng';
            klarnaSpecialPaymentLang ='se';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;
        case 'dk':
        case 'dnk':
            /*link_text_specialpayment = 'L&aelig;s mer'*/
            link_text_closebutton = 'Luk vindue';
            klarnaSpecialPaymentLang = 'dk';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;
        case 'no':
        case 'nok':
        case 'nor':
            /*link_text_specialpayment = 'Les mer'*/
            link_text_closebutton = 'Lukk';
            klarnaSpecialPaymentLang = 'no';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;
        case 'fi':
        case 'fin':
            /*link_text_specialpayment = 'Lue lis&auml;&auml;';*/
            link_text_closebutton = 'Sulje';
            klarnaSpecialPaymentLang = 'fi';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;
        case 'de':
        case 'deu':
            /*link_text_specialpayment = 'Lesen Sie mehr';*/
            link_text_closebutton = 'Schliessen';
            klarnaSpecialPaymentLang = 'de';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;
        case 'nl':
        case 'nld':
            /*link_text_specialpayment = 'Lees meer';*/
            link_text_closebutton = 'Sluit';
            klarnaSpecialPaymentLang = 'nl';
            klarnawidth = '500px';
            klarnahight = '300px';
            break;

    }
    // set the link text
    document.getElementById(obj).innerHTML = link_text_specialpayment;
    // Create the container element
    var div = document.createElement('div');
    div.id = 'klarna_specialpayment_popup';
    div.style.display = 'none';
    div.style.backgroundColor = '#ffffff';
    div.style.border = 'solid 1px black';
    div.style.width = klarnawidth;
    div.style.position = 'absolute';
    div.style.left = (document.documentElement.offsetWidth/2 - 250) + 'px';
    div.style.top = '50px';
    div.style.zIndex = 99999;
    div.style.padding = '10px';

    // create the iframe
    var iframe = document.createElement('iframe');
    iframe.id = 'iframe_klarna_specialpayment';
    iframe.frameBorder = 0;
    iframe.style.border = 0;
    iframe.style.width = klarnawidth;
    iframe.style.height = klarnahight;
    div.appendChild(iframe);

    // Create the a element that closes the popup
    var a = document.createElement('a');
    a.href = '#';
    a.style.color = '#000000';
    a.onclick = function() {
        document.getElementById('klarna_specialpayment_popup').style.display = 'none';
        return false;
    };
    
    // Create the link text
    a.innerHTML = link_text_closebutton;
    // Append the link to the div
    div.appendChild(a);

    // Append the div
    document.body.insertBefore(div,null);
}

// eid : Estore ID
// lang : The language in the popup (country code)
function ShowKlarnaSpecialPaymentPopup()
{
    var scroll = self.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;
    var top = scroll + 50;

    document.getElementById('klarna_specialpayment_popup').style.top = top + 'px';

    // Set the source for the iframe to the current language and estore    
    document.getElementById('iframe_klarna_specialpayment').src = 'https://static.klarna.com/external/html/vinter_' + klarnaSpecialPaymentLang + '.html';

    // Last we display the popup
    document.getElementById('klarna_specialpayment_popup').style.display = 'block';
}

// This method adds an event
function addKlarnaSpecialPaymentEvent(fn) {
    if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
    } else
{
        this.addEventListener('load', fn, false );
    }
}