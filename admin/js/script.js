    
// jQuery(document).ready(function($) {
    
//     function scrape() {
//         var link=document.getElementById("link").value;
//         var dtype=document.getElementById("ddtype").value;
//         get_data(link,dtype);
//     }
//     function ImportData(){
//         console.log(jQuery.parseJSON(jQuery('#frmdata').val())[0]);
//     }
//     function get_data(link,dtype) {
//         var ajaxurl = "<?php echo admin_url( 'admin-ajax.php'); ?>";
//         jQuery.ajax({
//             url : ajaxurl,
//             type : 'post',
//             data : {
//                 action : 'scrape_data',
//                 link :link,
//                 dtype: dtype,
//             },
//             success : function( response ) {
//                 var vae=jQuery.parseJSON(response);
//                 console.log(vae)
//                 jQuery('#frmdata').val(response);
//                 htmldata='<table><tr><th width="30px">Select</th><th>Name</th><th>Price</th><th>Content</th><th>Image</th></tr>';
//                 if(vae[1]=='product'){
//                     htmldata+= '<tr><td><input id="0" class="checkbox checkbox-primary styled" type="checkbox" checked></td><td><label>'+vae[0].title+'</label></td><td><label >'+vae[0].price+'</label></td><td><label >'+vae[0].content+'</label></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0].image+'" class="img-rounded"></td></tr></table>';
//                     jQuery('#data').html(htmldata);
//                 }
//                 else if(vae[1]=='shop'){
//                     var size=vae[0].length;
//                     for(var i=0;i<size;i++){
//                      htmldata += '<tr><td><input id="'+i+'" class="checkbox checkbox-primary styled" type="checkbox"></td><td><label>'+vae[0][i].title+'</label></td><td><label >'+vae[0][i].price+'</label></td><td><label >'+vae[0][i].content+'</label></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0][i].image+'" class="img-rounded"></td></tr>';   
//                     }
//                     jQuery('#data').html(htmldata+'</table>');
//                 }
//                 else if(vae[1]=='post'){

//                 }
//                 jQuery('#container').show();
//             }
//         });  
//     }
// });