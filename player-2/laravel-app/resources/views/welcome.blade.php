<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic Tac Toe Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f8f8;
        }

        #welcome {
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
            padding: 10%;
        }

        #message {
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
        }

        #turn {
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
            color: gray;
        }

        #members {
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
            color: darkblue;
        }

        #game-table {
            border-collapse: collapse;
            padding: 10%;
            pointer-events: none;
        }

        #game-table td {
            width: 100px;
            height: 100px;
            border: 2px solid #333;
            font-size: 24px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s;
            background-color: white;
        }

        #game-table td:hover {
            background-color: #eee;
        }

        #game-table td.unclickable {
            cursor: not-allowed;
        }
        
    </style>
</head>
<body>
    <div id="members"></div>

    <div id="message"></div>

    <div id="turn"></div>

    <table id="game-table">

        @for ($i = 0; $i < 3; $i++)
            <tr>
                @for ($j = 0; $j < 3; $j++)
                    <td id="grid-{{ $i }}.{{ $j }}" onclick="tick(this.id, 'O')"></td>
                @endfor
            </tr>
        @endfor

    </table>

    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

    <script>
        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            wsHost: "172.31.0.4",
            enabledTransports: ['ws'],
            forceTLS: false,
            enableStats: false,
            authEndpoint: "http://172.31.0.4:8000/api/auth",
            auth: {
                params: { playerId: 2 }
            }
        });

        pusher.connection.bind('state_change', function(states) {
            console.log('Connection State:', states.current);
        });
        
        const channel = pusher.subscribe('presence-TicTacToe')
        
        let gridState

        function initializeGrid() {
            gridState = [
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0]
            ]
        }

        window.onload = () => {
            initializeGrid()
            document.getElementById('game-table').style.pointerEvents = "none"
        }

        function tick(position, letter) {

            document.getElementById(position).innerHTML = letter
            let [i, j] = updateGridState(position, letter)

            channel.trigger('client-tick', {
                position: position,
                gridState: gridState
            })

            let winner = checkForWinner(i, j, gridState, letter)
            let draw = checkForDraw(gridState)

            if (winner) {
                document.getElementById('message').innerHTML = 'You win 🙂!'
                document.getElementById('message').style.color = 'green'

                channel.trigger('client-i-win', {})
                document.getElementById('turn').innerHTML = ""
            }
            else if (draw) {
                document.getElementById('message').innerHTML = 'It is a draw'
                document.getElementById('message').style.color = 'blue'

                channel.trigger('client-it-a-draw', {})
                document.getElementById('turn').innerHTML = ""
            }
            else {
                channel.trigger('client-your-turn', {})
                document.getElementById('turn').innerHTML = "It is player 1 turn"
            }

            document.getElementById(position).style.pointerEvents = "none"
            document.getElementById('game-table').style.pointerEvents = "none"
        }

        function updateGridState(position, letter) {
            let indices = position.split('-')[1].split('.')

            let i = parseInt(indices[0], 10)
            let j = parseInt(indices[1], 10)

            gridState[i][j] = letter
            return [i, j]
        }

        function checkForWinner(row, column, gridState, letter) {

            // check row
            if (gridState[row][0] == letter && (gridState[row][1] == letter) && 
                (gridState[row][2] == letter) ) return true
                
            // check column
            if (gridState[0][column] == letter && (gridState[1][column] == letter) && 
                (gridState[2][column] == letter) ) return true

            // check diagonal
            if ((gridState[0][0] == letter || gridState[0][2] == letter) && gridState[1][1] == letter) {

                // here we need to check, otherwise no need
                if (gridState[0][0] == letter && gridState[2][2] == letter) return true
                if (gridState[0][2] == letter && gridState[2][0] == letter) return true

            }

            return false
        }

        function checkForDraw(gridState) {

            let draw = true

            for (let row = 0; row < 3; row++) {
                if (gridState[row].includes(0)) {
                    draw = false
                    break
                }
            }

            return draw
        }

        channel.bind('pusher:subscription_succeeded', function(data) {

            if (data.count == 1)
                document.getElementById('members').innerHTML = "Waiting for player 1..."

            if (data.count == 2) {
                document.getElementById('members').innerHTML = "Player 1 is already here, enjoy the game"
                document.getElementById('turn').innerHTML = "It is player 1 turn"

                document.getElementById('game-table').style.pointerEvents = "none"
            }
        })
        
        channel.bind('pusher:member_added', function(data) {
            document.getElementById('members').innerHTML = "Player 1 have joined, enjoy the game"
            document.getElementById('turn').innerHTML = "It is player 1 turn"
        })

        channel.bind('pusher:member_removed', function(data) {
            document.getElementById('members').innerHTML = "Player 1 has left the game"
            document.getElementById('turn').innerHTML = ""

            document.getElementById('game-table').style.pointerEvents = "none"
        })

        channel.bind('client-tick', function(data) {

            gridState = data.gridState

            document.getElementById(data.position).innerHTML = 'X'
            document.getElementById(data.position).style.pointerEvents = "none"
        })

        channel.bind('client-i-win', function(data) {

            document.getElementById('message').innerHTML = 'You lose 😢'
            document.getElementById('message').style.color = 'red'

            document.getElementById('game-table').style.pointerEvents = "none"
        })

        channel.bind('client-it-a-draw', function(data) {
            document.getElementById('message').innerHTML = 'It is a draw'
            document.getElementById('message').style.color = 'blue'
            document.getElementById('turn').innerHTML = ""
        })

        channel.bind('client-your-turn', function(data) {
            document.getElementById('turn').innerHTML = "It is your turn"
            document.getElementById('game-table').style.pointerEvents = "auto"
        })

    </script>
</body>
</html>