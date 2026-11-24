<?php

include_once __DIR__ . '/../../includes/header.php';

?>

<body class="bg-gray-800 p-3 min-h-screen flex flex-col justify-between items-center">

    <div 
        x-data="{
            disabled: false,
            result: [],
            name: '',
            email: '',
            age: '',

            async submit() {
                this.disabled = true

                const res = await fetch('request.php', {
                    method: 'GET', 
                    headers: { 'Content-Type': 'application/json' },
                    
                })

                const data = await res.json()

                if(!res.ok) {
                    console.error(res.status, data)
                    this.disabled = false
                    return
                }

                console.log(data)

                this.result.push(data)

                console.log(this.result)
            }
        }"
        class="bg-gray-800 w-full max-w-sm rounded-2xl shadow-lg flex flex-col gap-4"
    >

        <input type="text" x-model="name" id="name" placeholder="Name"
            class="w-full p-3 rounded-lg text-gray-900 text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
        >

        <input type="email" x-model="email" id="email" placeholder="Email@test.com"
            class="w-full p-3 rounded-lg text-gray-900 text-center focus:outline-none focus:ring-2 focus:ring-blue-500" 
        >

        <input type="number" x-model="age" name="age" placeholder="age" 
            class="w-full p-3 rounded-lg text-gray-900 text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
        >

        <button 
            id="submit" 
            class="w-full p-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-500 disabled:cursor-not-allowed 
            transition-colors text-white rounded-lg font-medium" 
            
            @click="submit()"
        >Pošalji</button>

        

        <div x-show="result.length > 0">
            <template x-for="item in result">
                <div class="text-green-500" x-text="item">
                    
                </div>
            </template>
        </div>

    </div>

</body>

</html>