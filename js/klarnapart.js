var klarnapartpaymentlang = "";
var klarnapartpaymenteid = 0;
var klarnasum = 0;
function InitKlarnaPartPaymentElements(obj, eid, lang, linktext, sum)
{
		if (document.getElementById(obj) == null) {
			return;
		}
		klarnapartpaymentlang = lang;
		klarnapartpaymenteid = eid;
		klarnasum = sum;
		var link_text_partpayment = linktext;
		var link_text_closebutton = "St&auml;ng";
		var klarnawidth = '500px';
        var klarnahight = '630px';
		
		switch(lang)
		{
			case 'se':
			case 'swe':
				/*link_text_partpayment = "L&auml;s mer";*/
				link_text_closebutton = "St&auml;ng";
				klarnapartpaymentlang = "se";
				klarnawidth = '500px';
				klarnahight = '490px';
			break;
			case 'dk':
			case 'dnk':
				link_text_closebutton = "Luk vindue";
				/*link_text_partpayment = "L&aelig;s mere"*/
				klarnapartpaymentlang = "dk";
				klarnawidth = '500px';
				klarnahight = '530px';
			break;
			case 'no':
			case 'nok':
			case 'nor':
				link_text_closebutton = "Lukk";
				/*link_text_partpayment = "Les mer"*/
				klarnapartpaymentlang = "no";
				klarnawidth = '500px';
				klarnahight = '560px';
			break;
			case 'fi':
			case 'fin':
				/*link_text_partpayment = "Lue lis&auml;&auml;";*/
				link_text_closebutton = "Sulje";
				klarnapartpaymentlang = "fi";
				klarnawidth = '500px';
				klarnahight = '570px';
			break;
			case 'de':
			case 'deu':	
				/*link_text_partpayment = "Lesen Sie mehr!";*/
				link_text_closebutton = "Schliessen";
				klarnapartpaymentlang = "de";
				klarnawidth = '500px';
				klarnahight = '660px';
			break;
			case 'nl':	
			case 'nld':	
				/*link_text_partpayment = "Lees meer!";*/
				link_text_closebutton = "Sluit";
				klarnapartpaymentlang = "nl";
				klarnawidth = '510px';
				klarnahight = '690px';
			break;
		
		}
		// set the link text
		document.getElementById(obj).innerHTML = link_text_partpayment;		
		// Create the container element
		var div = document.createElement('div');
		div.id = 'klarna_partpayment_popup';
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
		iframe.id = 'iframe_klarna_partpayment';
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
			document.getElementById('klarna_partpayment_popup').style.display = 'none';
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
function ShowKlarnaPartPaymentPopup()
{	
			var scroll = self.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;
			var top = scroll + 50;		
			
			document.getElementById('klarna_partpayment_popup').style.top = top + 'px';
	
	// Set the source for the iframe to the current language and estore
	document.getElementById('iframe_klarna_partpayment').src = 'https://online.klarna.com/account_' + klarnapartpaymentlang + '.yaws?eid=' + klarnapartpaymenteid;
		
	// Last we display the popup
	document.getElementById('klarna_partpayment_popup').style.display = 'block';
}

// This method adds an event
function addKlarnaPartPaymentEvent(fn) {
  if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
  } else
  {
    this.addEventListener('load', fn, false );
	}
}