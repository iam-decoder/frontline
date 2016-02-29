(function ($, window, undefined)
{
    window.FES.Encryption = function ()
    {
        this.rsa = new RSAKey();
    }

    window.FES.Encryption.prototype.setPublic = function (n, e)
    {
        return this.rsa.setPublic(n, e);
    };

    window.FES.Encryption.prototype.encrypt = function (input)
    {
        return this.rsa.encrypt(input);
    };

})(jQuery, window);