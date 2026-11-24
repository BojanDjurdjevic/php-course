<?php

include_once __DIR__ . '/../../includes/header.php';

?>
<body class="bg-gray-900 p-3 min-h-screen flex justify-center items-center">

    <div 
        x-data="{
            disabled: false,
            result: [],
            name: '',
            email: '',
            age: '',

            async submit() {
                this.disabled = true

                const res = await fetch('users.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: this.name, email: this.email, age: Number(this.age) })
                })

                const data = await res.json()

                if(!res.ok) {
                    console.error(res.status, data)
                    this.disabled = false
                    return
                }

                console.log(data)

                this.result.push(data.data)
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
            :disabled="disabled"
            @click="submit()"
        >Pošalji</button>

        

        <div x-show="result.length > 0">
            <template x-for="item in result">
                <div>
                    <p class="text-red-500" x-text="item.name"> </p>
                    <p class="text-red-500" x-text="item.email">  </p>
                    <p class="text-red-500" x-text="item.age">  </p>
                </div>
            </template>
        </div>

    </div>
    
    


</body>
</html>