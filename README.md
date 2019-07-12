# Basic Chase / Orbital PHP Payment Processor
This is a very simple, very ugly class to process payments on the Chase Bank Orbital platform.
At first I was surprised that I couldn't find an official (or even semi-official) complete API package.
However, after learning that dealing with Chase is like pulling teeth, I kind of get why no one has put in the effort.

This is by no means perfect or complete or even guaranteed to work. No warranty is implied.

It's just a stripped down version of the bare minimum I needed to get simple payments processing in the certification environment.

Maybe it will help you out.

## Things that aren't working

 - AVS validation is roughed in but commented out
 - It's currently set to process in CAD only. For USD, change CurrencyCode to 840
 - It's currently set to process only on BIN 000002. If you need BIN 000001, then, change that too.
 - CVD validation is only supposed to be used by Visa or Discover (!?). It's only roughed and not working.


## Thanks
Most of the code for this class was cribbed directly from: [CartThrob/payments-chase-orbital-paymentech](https://github.com/CartThrob/payments-chase-orbital-paymentech/tree/master/payment_gateways)

