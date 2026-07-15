<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($actionTasks as $key => $task)
        @php
            $colorMap = [
                'amber' => [
                    'bg' => 'bg-amber-50 hover:bg-amber-100/70 border-amber-100/60',
                    'icon_bg' => 'bg-amber-100 text-amber-700',
                    'badge' => 'bg-amber-500 text-white',
                    'text' => 'text-amber-800',
                ],
                'blue' => [
                    'bg' => 'bg-blue-50 hover:bg-blue-100/70 border-blue-100/60',
                    'icon_bg' => 'bg-blue-100 text-blue-700',
                    'badge' => 'bg-blue-500 text-white',
                    'text' => 'text-blue-800',
                ],
                'red' => [
                    'bg' => 'bg-red-50 hover:bg-red-100/70 border-red-100/60',
                    'icon_bg' => 'bg-red-100 text-red-700',
                    'badge' => 'bg-red-500 text-white',
                    'text' => 'text-red-800',
                ],
                'purple' => [
                    'bg' => 'bg-purple-50 hover:bg-purple-100/70 border-purple-100/60',
                    'icon_bg' => 'bg-purple-100 text-purple-700',
                    'badge' => 'bg-purple-500 text-white',
                    'text' => 'text-purple-800',
                ],
                'gray' => [
                    'bg' => 'bg-gray-50 hover:bg-gray-100/70 border-gray-100/60',
                    'icon_bg' => 'bg-gray-200 text-gray-700',
                    'badge' => 'bg-gray-500 text-white',
                    'text' => 'text-gray-800',
                ],
                'indigo' => [
                    'bg' => 'bg-indigo-50 hover:bg-indigo-100/70 border-indigo-100/60',
                    'icon_bg' => 'bg-indigo-100 text-indigo-700',
                    'badge' => 'bg-indigo-500 text-white',
                    'text' => 'text-indigo-800',
                ],
            ];
            $style = $colorMap[$task['color']] ?? $colorMap['blue'];
        @endphp
        
        <a href="{{ $task['link'] }}" 
           class="dashboard-action-card p-4 bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:-translate-y-0.5 hover:shadow-md transition-all group duration-200 relative overflow-hidden">
            <!-- Background light hint -->
            <div class="absolute inset-0 {{ $style['bg'] }} opacity-20 pointer-events-none"></div>

            <div class="flex items-center justify-between relative z-10">
                <span class="material-symbols-outlined text-[22px] p-2.5 rounded-xl {{ $style['icon_bg'] }} shrink-0">
                    {{ $task['icon'] }}
                </span>
                
                @if ($task['count'] > 0)
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-bold rounded-full {{ $style['badge'] }}">
                        {{ $task['count'] }}
                    </span>
                @else
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-semibold text-gray-400 bg-gray-50 rounded-full border border-gray-100">
                        Đã xong
                    </span>
                @endif
            </div>

            <div class="mt-4 relative z-10">
                <h4 class="text-sm font-bold text-gray-900 group-hover:text-emerald-700 transition-colors">{{ $task['label'] }}</h4>
                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $task['desc'] }}</p>
            </div>
        </a>
    @endforeach
</div>
