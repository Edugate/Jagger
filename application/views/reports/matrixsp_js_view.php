//<Google only allows the node name without the afp: prefix. the other browser require this.
var prefix="";
var policies=new Array();
var Names= new Array();
var providerURL = new Array(); 
var Locations = new Array();
var resource;
var locationInXml;
var AttribName= new Array();
var AttribStatus = new Array();
var noArp = new Array();
var noArpprov = new Array();
var noArpsite = new Array();
var Arpsite=new Array();

function matrixinit(){
	resource="<?php echo $entityid; ?>";

	var locations=new Array();
	var name = new Array();
	var prov = new Array();
	var siteloc=new Array();

	if (window.XMLHttpRequest){
                xmlhttp=new XMLHttpRequest();
        }
        else{
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        try{
                xmlhttp.open("GET","<?php echo $sites_url ;?>",false);
                xmlhttp.send();
                xmlDoc=xmlhttp.responseXML;
        }
        catch(error){
                alert(error.message);
        }

	var details = xmlDoc.getElementsByTagName("Attribute");

	
	for(j=0;j<details.length;j++){
		AttribName[j]=details[j].getElementsByTagName("AttributeName")[0].childNodes[0].nodeValue;
		AttribStatus[j]=details[j].getElementsByTagName("Status")[0].childNodes[0].nodeValue.charAt(0);
	}

	var x=xmlDoc.getElementsByTagName("site");


	for(i=0;i<x.length;i++){
		name[i]= x[i].getElementsByTagName("Name")[0].childNodes[0].nodeValue;
		locations[i]=x[i].getElementsByTagName("Location")[0].childNodes[0].nodeValue;
		prov[i] = x[i].getElementsByTagName("entityID")[0].childNodes[0].nodeValue;
		siteloc[i] = x[i].getElementsByTagName("providerURL")[0].childNodes[0].nodeValue;
	}	

	var returns = true; 

	for(p=0;p<locations.length;p++){
		site=locations[p];
		returns = xmlParser(site,name[p],prov[p],siteloc[p]);
	
		if(returns){
		Names[Names.length]=name[p];	
		Locations[Locations.length]=locations[p];	
		providerURL[providerURL.length]=prov[p];
		Arpsite[Arpsite.length] = siteloc[p];
		}

	}	
	printout();
}

function xmlParser(site,name,noprov,noArpsites){

	var Name=new Array();

	if (window.XMLHttpRequest){
                xmlhttp=new XMLHttpRequest();
        }
        else{
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        try{
                xmlhttp.open("GET",site,false);
                xmlhttp.send();
                xmlDoc=xmlhttp.responseXML;
        }
        catch(error){
                alert(error.message);
        }

if(xmlDoc!=null){
	//Test with chrome, if 0 length then its firefox or explorer and prefix is needed.
	var x= xmlDoc.getElementsByTagName("AttributeFilterPolicy");

	if(x.length==0){
		prefix="afp:";
		x= xmlDoc.getElementsByTagName(prefix + "AttributeFilterPolicy");
	}

        var temp;
	var Notice = new Array();
	var newName=false;
	var firstiteration=true;
	var multi=0;
	
	var Ress=new Array();

        for(i=0;i<x.length;i++){
		if(x[i].attributes.getNamedItem("id").value==resource){
			locationInXml = i+1;
			break;
		}
	}

	var AttributeRules= new Array();
	var permit = new Array();
	var special = new Array();

	if(locationInXml!=undefined){	
		var p = x[locationInXml-1].getElementsByTagName(prefix + "AttributeRule");
		
		for(k=0;k<p.length;k++){
			var dupe=false;
			
			for(pp=0;pp<AttributeRules.length;pp++){

				if(p[k].attributes.getNamedItem("attributeID").value==AttributeRules[pp]){
					dupe=true;
					break;
				}

			}
			
			if(!dupe){
			var tmp="none";
		                if(p[k].getElementsByTagName(prefix + "DenyValueRule").length>0){
			
				
					if(p[k].getElementsByTagName(prefix + "DenyValueRule")[0].getElementsByTagName(prefix + "Rule").length > 0){
						tmp="yes";
					}
	
					special[special.length]=tmp;
			                permit[permit.length] = "deny";
		                }
		                else{
 	                               if(p[k].getElementsByTagName(prefix + "PermitValueRule")[0].getElementsByTagName(prefix + "Rule").length > 0){
	                                     	 tmp="yes";
                                       }
					special[special.length]=tmp;
			                permit[permit.length] = "allow";
		                }


				AttributeRules[AttributeRules.length++] = p[k].attributes.getNamedItem("attributeID").value;
			}
		}
	
		if(policies[policies.length]==undefined){
			policies[policies.length]=new Array();
		}

		var Loc = policies.length-1;
		policies[Loc][0]= new PolicyAttributes( AttributeRules, Name[0],Notice,permit,special);	
	}

  return true;
  }else{
	noArp[noArp.length]=name;
	noArpprov[noArpprov.length]=noprov;
	noArpsite[noArpsite.length]=noArpsites;
	return false;
	}
}

function printout(){
	var attributes=new Array();
	var color=new Array();

	if(policies.length>0){
		for(p=0;p<Locations.length;p++){

			for(pp=0;pp<policies[p].length;pp++){
				for(i=0;i<policies[p][0].AttributeRules.length;i++){

					var duplicate=false;
	
					for(k=0;k<attributes.length;k++){
				
						if(policies[p][pp].AttributeRules[i]==attributes[k]){			
							duplicate=true;
						}
					}

					if(!duplicate){				
						attributes[attributes.length]=policies[p][pp].AttributeRules[i];
						color[color.length] = "0000FF";
					}
       				}
			}
		}

		for(pp=0;pp<policies.length;pp++){

			for(i=0;i<policies[pp].length;i++){

				if(policies[pp][i].Notice!=undefined){

					for(j=0;j<policies[pp][i].Notice.length;j+=2){

						var duplicate=false;

						for(k=0;k<attributes.length;k++){
				
							if(policies[pp][i].Notice[j]==attributes[k]){
								duplicate=true;
							}
						}	

						if(!duplicate && policies[pp][i].Notice[j]!=undefined){
							attributes[attributes.length++]=policies[pp][i].Notice[j];
							color[color.length++]="FF0000";
						}
					}	
				}
			}
		}

	var table = "<br /><br /><br /><table>";

	table+="<tr><th style=text-align:left;vertical-align:bottom;><img src = \"https://middleware-dev.heanet.ie/rr3/images/legend.png\" />NAME</th>";

	for(i=0;i<attributes.length;i++){
		table+="<td><img src=\"../../view_attribute_matrix/show/"+attributes[i] +"/"+color[i] + "/yes" +"\"></td>";	
	}



	var isNone=true;
	var tempname="";

	for(l=0;l<attributes.length;l++){
		isNone=true;
		for(g=0;g<AttribName.length;g++){
			tempname = AttribName[g];
				if(attributes[l]==AttribName[g]){
				isNone=false;
				break;	
			}
		}

		if(isNone && tempname!=""){
			isNone=true;

			for(a=0;a<attributes.length;a++){
				if(attributes[a]==tempname){
					isNone=false;
					break;
				}				
			}
			
			if(isNone){
			table+="<td><img src=\"../../view_attribute_matrix/show/"+tempname +"/"+"545454" + "/yes" +"\"></td>";
			attributes[attributes.length] = tempname;
			tempname="";
			}
		}
	}

	table+="</tr>";

	var status='&nbsp;';

	for(i=0;i<policies.length;i++){
		table+="<tr>";
		table+="<td>";
		table+="<div title=\"" + providerURL[i] + "\"><a href = \""+Arpsite[i]+"\">" + Names[i] +"</a></div>";	
		table+="</td>";
			
		for(j=0;j<attributes.length;j++){			
					 for(o=0;o<AttribName.length;o++){
                                                if(AttribName[o]==attributes[j]){
                                                        status=AttribStatus[o];
                                                        break;
                                                }
                                            }

			for(pp=0;pp<policies[i].length;pp++){	
				var none=true;



				for(k=0;k<policies[i][pp].AttributeRules.length;k++){
			
					if(policies[i][pp].AttributeRules[k]==attributes[j]){


						if(policies[i][pp].Special[k]=="yes"){
						        table+="<td class=\"spec\">" + status +  "</td>";
						}
						else{
							if(policies[i][pp].Allowordeny[k] == "deny"){
	                                                	table+="<td class=\"den\">" + status +  "</td>";
							}
							else{
					        		table+="<td class=\"perm\">" + status +  "</td>";
							}
						}
						none=false;
						status='';
						break;
						
                                        }		
				 }

				if(none){	
					if(status.length>0){		
					table+="<td class=\"dis\">" + status.toUpperCase() + "</td>";
					status='';
					}
					else{
					 table+="<td class=\"dis\">&nbsp;</td>";
					}
				}
			}
		}
		table+="</tr>";
	}

	if(noArp.length>0){
		for(i=0;i<noArp.length;i++){
		       table+="<tr>";
		       table+="<td>";
	               table+="<div title=\""+ noArpprov[i]  +"\">"+ "<a href=\"" +  noArpsite[i]  +  "\">"     +   noArp[i] +"</a></div>";
        	       table+="</td>";

			for(j=0; j<attributes.length; j++){

				  for(o=0;o<AttribName.length;o++){
                                                if(AttribName[o]==attributes[j]){
                                                        status=AttribStatus[o];
                                                        break;
                                                }
                                   }

				if(status.length>0){
				table+="<td class = \"dis\">" + status.toUpperCase() + "</td>";
				status='';
				}
				else{
				table+="<td class = \"dis\">&nbsp;</td>";			
				}
			}
		}
	}
	table+="</table>";
	document.getElementById("matrixtable").innerHTML=table;
	}
   	else{
		alert("Cannot find resource name");
  	}
}

function PolicyAttributes( AttributeRules, name,Notice,allowordeny,special){ 
//Values from attributes
	this.AttributeRules=AttributeRules
//Value from comments
	this.Name=name;
	this.Notice=Notice;
//permit or deny
	this.Allowordeny =  allowordeny;
	this.Special = special;
}
