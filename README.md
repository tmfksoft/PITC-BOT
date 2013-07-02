=========================
= PITC v1.1 Development =
=========================

What is PITCBots?
-------------
PITCBots is a fork of the PITC Project, a completely opensource IRC Client.
PITCBots differs from PITC in the fact it is now a seperate project that will update at different times.
PITCBots is more practical than PITC due to the removed GUI in PITCBots, now you can just start your bot and stop it like a normal scripted bot but using existing or custom coded PITC Scripts.

What does PITCBots require?
-----------------------
PITCBots merely requires a bare bones PHP install
of version 5 or above.
Most VPS' or Mac systems come with PHP preinstalled
so users needn't worry.

What Operating Systems has PITC been tested on?
-----------------------------------------------
PITC has been tested on Ubuntu, Debian, Mac OSX
and Windows. Due to the lack of GUI PITC now works on ANY platform that supports PHP5+
So have fun hosting bots on any machine you want.

What makes PITCBots better than PITC for IRC Bots?
---------------------------------------------------
PITC is aimed toward those looking for a simple IRC Client for their server,
as most servers tned to have PHP installed PITC can be put on the server without any hassle.
PITC also has a GUI with abilities to switch between channels and PM's with ease.
However PITCBots does not have any GUI like PITC does. This means you wont need a decent connection to use PITCBots.
Due to the lack of GUI PITCBots does not accept input like PITC Does which is great for making a simple IRC Bot that sits and does whats needed without having to keep a GUI updated.
PITCBots has been crafted specially for bot hosting unlike PITC which leans heavily toward being an IRC Client.

How do I setup PITCBots without a GUI?
--------------------------------------
If you have PITC you can copy your config over and edit the config in any text editor.
Otherwise you can copy the config below and edit to suit:

lang=EN
nick=MyBot
altnick=MyBot%5BPING%5D
email=myemail%40somehost.com
realname=My Bots real name
address=irc.someircnet.com
quit=Good Bye!
password=
ajoin=%23mychannel

Credits
--------
Aha2y - Creating the Uptime Script http://www.hawkee.com/snippet/9711/ which has been intergrated into PITC v1.1 and replaces the Uptime CTCP with new code, also adds the function string_duration();

PITCBots is Opensource! I'm going to steal it and call it my own!
-------------------------------------------------------------
No you're not. PITCBots is OpenSource but you HAVE to give credit
if you decide to fork and create your own client from PITC,
this code has been created by myself (Thomas Edwards) and is
my own work not yours! It currently IS on GITHub so it will start
to comprimise of other peoples work. Credit those where needed!

Forking Requirements
---------------------
I don't personally mind if you fork PITCBots as your own platform as long as in your credits you drop a small message saying where your project was forked from!
