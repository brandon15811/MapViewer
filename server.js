var fs = require('fs');
var path = require('path');
var dgram = require("dgram");
var util = require('util');
var app = require('http').createServer(handler);
var io = require('socket.io').listen(app);
var EventEmitter = require('events').EventEmitter;
var node_static = require('node-static');

var server = new EventEmitter();

//Stuff to make debugging easier
try
{
    //Appends file and line numbers to console output
    require('console-trace')({
        always: true,
        cwd: __dirname
    })
    //Longer Stacktraces
    require('longjohn');
}
catch(err)
{}

var udpserver = dgram.createSocket("udp4");
var players = {};
players['players'] = {};

//HTTP
app.listen(9615);
//UDP
udpserver.bind(4629);

function handler(req, res)
{
    var file = new node_static.Server(path.join(__dirname, '/web/'), {cache: 1});
    file.serve(req, res, function(err, result)
    {
        res.writeHead(404);
        return res.end('File not found');
    });
}

udpserver.on("message", function (msg, rinfo)
{
    console.log("server got: " + msg + " from " + rinfo.address + ":" + rinfo.port);
    var data = JSON.parse(msg);
    switch(data.event)
    {
        case "server.unknownpacket":
            players = JSON.parse(msg);
            console.log("derp" + players)
            io.sockets.on('connection', function (socket)
            {
                socket.on('mapLoaded', function()
                {
                    console.log('hi' + JSON.stringify(players))
                    socket.emit('initPlayers', players);
                });
            });
            delete players['event'];
            break;
        case "player.offline.get":
        case "player.move":
            io.sockets.emit(data.event, data);
            if (typeof(players['players'][data.player]) === 'undefined')
            {
                players['players'][data.player] = {};
            }

            var playerArray = players['players'][data.player];
            playerArray['x'] = data['x'];
            playerArray['y'] = data['y'];
            playerArray['z'] = data['z'];
            break;
        case "player.quit":
            io.sockets.emit('player.quit', data);
            delete players['players'][data.player];
            break;
    }
});

udpserver.on("listening", function ()
{
    var address = udpserver.address();

    console.log("udpserver listening " + address.address + ":" + address.port);
    var jsonstr = new Buffer("MV" + JSON.stringify({"event": "getAllPlayers"}));
    udpserver.send(jsonstr, 0, jsonstr.length, 19132, "127.0.0.1");

});
