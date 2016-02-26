window.FES = {
    singleton: {}
};

window.FES.Encryption = function(){
    this.rsa = new RSAKey();
};

window.FES.Encryption.prototype.setPublic = function(n,e){
    return this.rsa.setPublic(n,e);
};

window.FES.Encryption.prototype.encrypt = function(input){
    return this.rsa.encrypt(input);
};

(function ($, FES, undefined)
{
    $(function ()
    { //after the fold
        var $rsa_node = $('#page-data');
        if($rsa_node.length > 0) {
            var rsa_data = $rsa_node.data('cookieString');
            if(rsa_data.hasOwnProperty('n') && rsa_data.hasOwnProperty('e')) {
                FES.singleton.encryption = new FES.Encryption();
                FES.singleton.encryption.setPublic(rsa_data['n'], rsa_data['e']);
            }
            $rsa_node.remove();
        }

        $(document).on('submit', 'form', function(e){
            e.preventDefault();
            $form = $(this);
            var form_data = $form.serializeObject();
//            console.log(form_data);
//            return false;
            if(form_data && typeof form_data.password === "string" && form_data.password.length > 0){
                form_data.password = FES.singleton.encryption.encrypt(form_data.password);
            }

            $.ajax({
                type: $form.attr('method'),
                async: true,
                url: $form.attr('action'),
                data: form_data,
                dataType: "html",
                crossDomain: true,
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                xhrFields: {
                    withCredentials: true
                },
                success: function(html){
                    $('body').empty();
                    $('body').append(html);
                },
                error: function(data){
                    if(Object.prototype.toString.call(data) === "[object String]"){
                        if(data.indexOf("{") === 0){
                            data = JSON.parse(data);
                            if(data.errors){
                                $.each(data.errors, function(i,e){
                                    console.log(e);
                                });
                            }
                        }
                    } else {
                        console.log(data);
                    }
                }
            });
            return false; //stop the click propagation
        });
    });
})(jQuery, window.FES);