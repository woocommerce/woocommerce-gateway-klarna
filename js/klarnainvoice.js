var klarnainvoicelang = "";
var klarnainvoiceeid = 0;
var klarnainvoiceefee = 0;
function InitKlarnaInvoiceElements(obj, eid, lang, charge)
{
		if (document.getElementById(obj) == null) {
			return;
		}
		klarnainvoicelang = lang;
		klarnainvoiceeid = eid;
		if(charge)
			klarnainvoiceefee = charge;
		else
			klarnainvoiceefee = 0;
		var link_text_invoice = "Villkor f&ouml;r faktura";
		var link_text_closebutton = "St&auml;ng";
	    var klarnawidth = '500px';
        var klarnahight = '630px';
		switch(klarnainvoicelang)
		{
			case 'se':
			case 'swe':
				link_text_invoice = "Villkor f&ouml;r faktura";
				link_text_closebutton = "St&auml;ng";
				klarnainvoicelang = "se";
				klarnawidth = '500px';
				klarnahight = '510px';
			break;
			case 'dk':
			case 'dnk':
				link_text_closebutton = "Luk vindue";
				link_text_invoice = "Vilk&aring;r for faktura"
				klarnainvoicelang = "dk";
				klarnawidth = '500px';
				klarnahight = '490px';
			break;
			case 'no':
			case 'nok':
			case 'nor':
				link_text_invoice = "Vilk&aring;r for faktura"
				link_text_closebutton = "Lukk";
				klarnainvoicelang = "no";
				klarnawidth = '500px';
				klarnahight = '490px';
			break;
			case 'fi':
			case 'fin':
				link_text_invoice = "Laskuehdot";
				link_text_closebutton = "Sulje";
				klarnainvoicelang = "fi";
				klarnawidth = '500px';
				klarnahight = '500px';
			break;
			case 'de':	
			case 'deu':	
				link_text_invoice = "Rechnungsbedingungen";
				link_text_closebutton = "Schliessen";
				klarnainvoicelang = "de";
				klarnawidth = '500px';
				klarnahight = '570px';
			break;
			case 'nl':
			case 'nld':
				link_text_invoice = "Factuurvoorwaarden";
				link_text_closebutton = "Sluit";
				klarnainvoicelang = "nl";
				klarnawidth = '500px';
				klarnahight = '510px';
			break;
		
		}
		// set the link text
		document.getElementById(obj).innerHTML = link_text_invoice;
		// Create the container element
		var div = document.createElement('div');
		div.id = 'klarna_invoice_popup';
		div.style.display = 'none';
		div.style.backgroundColor = '#ffffff';
		div.style.border = 'solid 1px black';
		div.style.width = klarnawidth;
		div.style.position = 'absolute';
		div.style.left = (document.documentElement.offsetWidth/2 - 250) + 'px';
		div.style.top = '50px';
		div.style.zIndex = 9999;
		div.style.padding = '10px';
		
		// create the iframe
		var iframe = document.createElement('iframe');
		iframe.id = 'iframe_klarna_invoice';
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
			document.getElementById('klarna_invoice_popup').style.display = 'none';
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
// type : 1 = Klarna Konto, 2 = Faktura
function ShowKlarnaInvoicePopup()
{		
			var scroll = self.pageYOffset||document.body.scrollTop||document.documentElement.scrollTop;
			var top = scroll + 50;		
									
			document.getElementById('klarna_invoice_popup').style.top = top + 'px';
		
		if(klarnainvoicelang == "se")
			document.getElementById('iframe_klarna_invoice').src = 'https://online.klarna.com/villkor.yaws?eid=' + klarnainvoiceeid + '&charge=' + klarnainvoiceefee;
		else
			document.getElementById('iframe_klarna_invoice').src = 'https://online.klarna.com/villkor_' + klarnainvoicelang + '.yaws?eid=' + klarnainvoiceeid + '&charge=' + klarnainvoiceefee;
			
		
			
		document.getElementById('klarna_invoice_popup').style.display = 'block';
}

// This method adds an event
function addKlarnaInvoiceEvent(fn) {
  if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
  } else
  {
    this.addEventListener('load', fn, false );
	}
}