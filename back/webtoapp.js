
/*
botsinc_id_input  = document.getElementById("botsinc_id_input");
botsinc_id_submit = document.getElementById("botsinc_id_submit");

window.addEventListener("message", (event) =>
{
	console.log(event);
	
	if (event.origin !== "https://bots-inc.com")
		return;
	
	if( botsinc_id_input && botsinc_id_submit ) 
	{
		var id = event.data.bot_id;
		
		botsinc_id_input.value = id;
		botsinc_id_submit.click();
	}
	

});*/