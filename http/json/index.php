<?php

include_once __DIR__ . '/../../includes/header.php';

?>
<body class="bg-gray-900 pa-3 flex justify-center items-center">

    <div 
        x-data="{disabled: false}"
        class="bg-gray-900 w-2/5 h-2/5 flex flex-col justify-center content-around"
    >

        <input type="text"  class="w-auto p-6 m-5 text-gray-900 rounded-xl text-center" id="name" placeholder="Name">

        <input type="text" class="w-auto p-6 m-5 text-gray-900 rounded-xl text-center" id="email" placeholder="Email@test.com">

        <button 
            id="submit" class="w-auto p-2 m-1 bg-gray-500 text-white rounded-xl" 
            :disabled="disabled"
            @click="disabled = true"
        >Pošalji</button>

    </div>
    
    

    <script>
        
        const name = document.querySelector("#name")

        const email = document.querySelector("#email")

        const btn = document.querySelector("#submit")

        btn.addEventListener('click', () => {
            let obj = {
                'name': name.value,
                'email': email.value
            }

            fetch('json.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(obj)
            })
            .then(async res => {
                const data = await res.json()

                if (!res.ok) {
                    console.error(res.status, data)
                    return
                }

                console.log(data)
            })
        })

    </script>
</body>
</html>