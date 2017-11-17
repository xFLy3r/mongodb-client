Use following cmds for installing: <br>
`sudo docker run --name test-mongo -d mongo` <br>
`sudo docker build -t mongodb-client .` <br>
`sudo docker run -it --rm --name mongodb-test --link test-mongo:mongo  mongodb-client`
