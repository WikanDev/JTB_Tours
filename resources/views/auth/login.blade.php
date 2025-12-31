<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>JTB Tours</title>
  @vite('resources/css/app.css')
  <style>
    /* Opsional: tambahkan jika Tailwind tidak auto-generate utility ini */
    .form-control:focus {
      @apply ring-2 ring-blue-500 outline-none;
    }
  </style>
</head>
<body class="bg-gray-50">
  {{-- Include flash notification card --}}
  <x-notification-card />
  
  <section class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
    
    <div class="mb-10">
      <img src="{{ asset('img/JTB_logo.png') }}" alt="JTB Tours Logo" class="h-16 sm:h-20 object-contain">
    </div>

    
    <div class="w-full max-w-md">
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 sm:p-8">
          <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Selamat Datang</h1>
          <p class="text-gray-500 text-center text-sm mb-6">Silakan masuk ke akun Anda</p>

          
          
          <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-5">
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input 
                type="email" 
                id="email"
                name="email" 
                value="{{ old('email') }}" 
                required 
                autofocus 
                class="form-control w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              >
              @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div class="mb-6">
              <div class="flex justify-between items-center mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                
              </div>
              <input 
                type="password" 
                id="password"
                name="password" 
                required 
                class="form-control w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              >
              @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <button 
              type="submit" 
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Masuk
            </button>
          </form>
        </div>
      </div>

      
      <p class="text-center text-gray-400 text-xs mt-4">
        &copy; {{ date('Y') }} JTB Tours. All rights reserved.
      </p>
    </div>
  </section>
</body>
</html>