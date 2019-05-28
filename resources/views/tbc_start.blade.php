<html>
    <head>
        <title>TBCPAY</title>
        <script type="text/javascript" language="javascript">
            function redirect() {
                document.returnform.submit();
            }
        </script>
    </head>

    @if (isset($start['error']))

        <body>
            <h2>Error:</h2>
            <h1>{{ $start['error'] }}</h1>
        </body>

    @elseif (isset($start['TRANSACTION_ID']))

        <body onLoad="javascript:redirect()">
            <form name="returnform" action="{{ config('tbc.form_url', 'https://securepay.ufc.ge/ecomm2/ClientHandler') }}" method="POST">
                <input type="hidden" name="trans_id" value="{{ $start['TRANSACTION_ID'] }}">

                <noscript>
                    <center>Please click the submit button below.<br>
                        <input type="submit" name="submit" value="Submit">
                    </center>
                </noscript>
            </form>
        </body>

    @endif

</html>
