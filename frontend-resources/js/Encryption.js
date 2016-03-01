(function ($, window, undefined)
{
    window.FES.Encryption = function ()
    {
        //public
        this.rsa = new RSAKey();

        //public
        this.setPublic = function (n, e)
        {
            return this.rsa.setPublic(n, e);
        };

        //public
        this.encrypt = function (input)
        {
            return this.rsa.encrypt(input);
        };
    };

})(jQuery, window);