# Laravel Echo Example

## Installing

These are the general steps required to get a basic echo example working using socket.io and redis!

Make sure you have Redis installed on osx you can do `brew install redis && brew services start redis`

Then create a new Echo project e.g. `composer create-project laravel/laravel echo-example`

To communicate with Redis, you need to install predis with `composer require predis/predis`

Then we need echo and its dependencies available with `npm i laravel-echo laravel-echo-server socket.io-client`

- laravel-echo is the library to add a more simple interface on top of socket.io
- laravel-echo-server is the websocket server that will communicate with Redis and your browser
- socket.io-client is the socket.io api library which laravel-echo uses under the hood

Once everything is installed we can then edit our `.env` file to use the redis `BROADCAST_DRIVER`

We'll then need to generate an echo server config e.g `./node_modules/.bin/laravel-echo-server init`

This will run through a wizard and create a `laravel-echo-server.json` once generated double check it to make sure everything is setup correctly, e.g hostnames, ports, ssl certs etc.

Next we'll need to setup laravel auth using `php artisan make:auth` you'll need to spin up a database and update the `.env` file with your database details, once that is done we can migrate the database with `php artisan migrate`

## Configuring

As not every application needs broadcasting, its disabled by default, we need to edit the `config/app.php` to enable it.

Once enabled you can setup a route in the `channels.php` e.g

```php
Broadcast::channel('everywhere', function ($user) {
   return $user;
});
```

Next we need to setup our javascript by importing echo and socket.io and just join a generic presence channel

```js
import Echo from "laravel-echo"

window.io = require('socket.io-client');

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001'
});

window.Echo.join('everywhere');
```

We can now start our echo server to by running `./node_modules/.bin/laravel-echo-server` - it should start up all happily!

Now make sure the js is compiled, if you're using mix then `npm run watch` should be fine!

When you now register/login you should see some output in the console!

## Listening to channel subscriptions

We can chain more methods to the `join('everywhere')` to listen for other events, the 3 we'll use first are

- .here(users) - this fires when the user loading the page establishes a connection to the websocket server, it gets an array of all the other subscribed users
- .joining(user) - when *another* user joins the channel this fires and gets an instance of the user
- .leaving(user) - when *another* users connection is closed, this fires and gets an instance of the user.

You can look at `bootstrap.js` to see examples of what you can do

## Listening to PHP events

One key factor is listening for PHP events, this might be things such as UserRegistered, PageEdited, OrderCreated etc

Laravel will fire the event, which gets pushed to the Redis queue, which will then notify the echo server, which will then broadcast the event.

We can make an event with something like `php artisan make:event UserRegisteredEvent`

To make the event broadcastable we need to make sure it implements `ShouldBroadcast`

The main 2 methods we'll need are `broadcastOn` and `broadcastWith`

By default any public properties on the event class will get passed to echo, so its safer to create a `broadcastWith` method which returns an array of data you want to share.

We also pick the type of channel we want to broadcast to and its name.

As we wan't to broadcast to everybody who is on the site, we change it to a `PresenceChannel` and use the name `everywhere` which we used earlier.

We then fire the event appropriately, as we made a `UserRegisteredEvent` we will call it inside the `RegisterController.php` by using

```php
event(new UserRegisteredEvent($user));
```

Then in our javascipt we `.listen` for the event e.g.

```js
Echo
.join('everywhere')
.listen('UserRegisteredEvent', function (data) {
    console.log(data);
})
```

Now you can do what ever is needed on your frontend, e.g display notificaion etc.

## Listening to Javascript events

Echo gives you basic functionality to communicate purely via websockets and javascript via 2 main methods

- .whisper('event-name', data) - this triggers the event-name listeners and attaches the data supplied
- .listenForWhisper('event-name', fn => data) - this listens for the event-name and receives thed data as its arg

example might be

```js
Echo.private('chat.1')

.whisper('level-up', {name: 'david', level: 5})

.listenForWhisper('level-up', user => {
    alert(user.name increased to level user.level);
});
```

Annoyingly only .private channels have the `whisper` and `listenForWhisper` functions, however these just use socket.io emit methods under the hood, so you can replace this functionality yourself or hit socket.io direct e.g

```js
this.socket.emit('client event', {
    channel: 'everywhere',
    event: `client-event-name`,
    data: data
});
```

As certain users might not be allowed to subscribe to channels, you need to make sure you set up your route correctly.

Depending on your channel type, your route will need to do one of the following:

- return true/false - if the user has access to that channel
- return a user object - to identify who that user actually is, it needs a minimum of `id` and `name` fields

Presence channels require a user object to know who to announce as joined.

Other channels just need a true/false to authorise you e.g

```php
Broadcast::channel('everywhere', function ($user) {
   return $user;
});

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return $user->canAccessRoom($roomId);
});
```

## Thats it!

Obviously you can get much more creative, but these are the main backbone basics of using it, you can clone the repo and run it yourself for more bulked out examples.

```
git clone git@github.com:Selesti/Laravel-Echo-Example.git
```