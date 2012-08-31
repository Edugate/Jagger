var value="";
var Attribute="";
var allowordeny="<DenyValueRule xsi:type=\"basic:ANY\"/>";

function updateSelection(form){

	if(document.getElementById("mySelect").selectedIndex!=0){
		Attribute =form.mySelect.value;
	}

        switch(document.getElementById("mySelect").selectedIndex){
        case 1:
               form.txtValue.value="name@example.ie"
                break;
        case 2:
               form.txtValue.value="John Doe"
                break;
        case 3:
                form.txtValue.value="Doe";
                break;
        case 4:
                form.txtValue.value="552523";
                break;
        case 5:
                form.txtValue.value="example";
                break;
        case 6:
                form.txtValue.value="PrincipleExample";
                break;
        }

}

function allow(form){
	if(form.btnDeny.checked || form.btnAllow.checked || form.btnAllow.checked==false){
		allowordeny="<AllowValueRule xsi:type=\"basic:ANY\"/>";
		form.btnDeny.checked=false;
		form.btnAllow.checked=true; //in case its already checked and you clicked again
	}

}


function deny(form){
        if(form.btnAllow.checked || form.btnDeny.checked==false || form.btnDeny.checked==false){
                allowordeny="<DenyValueRule xsi:type=\"basic:ANY\"/>";
                form.btnAllow.checked=false;
		form.btnDeny.checked=true;
        }

}

function Submit(submit){

//Submitted (it is encoded in the html side using ESAPI)

}

function AddXML(form, val){
	value=val;

	if(value!="" && allowordeny!="" && Attribute!=""){
		if(form.xmlAreaBox.value==""){
			form.xmlAreaBox.value+="<PolicyRequirementRule xsi:type=\"basic:AttributeValueString\" attributeID=\"" + Attribute + "\" value=\""+ value +"\"/>";
		}
		else{
			form.xmlAreaBox.value+="\n\n<PolicyRequirementRule xsi:type=\"basic:AttributeValueString\" attributeID=\"" + Attribute + "\" value=\""+ value +"\"/>";
		}
        form.xmlAreaBox.value+="\n	<AttributeRule attributeID=\""+ Attribute + "\">";
        form.xmlAreaBox.value+="\n	" + allowordeny;
	}
	else{

		if(value==""){
			alert("A value must be entered");
		}
		else if(Attribute=""){
			alert("Please select an Attribute ID");
		}
		else if(allowordeny==""){
			alert("Please select whether you want to allow or deny");
		}
	}
}
