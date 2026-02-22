@extends('layouts.admin')

@section('content')
@php
	$summary = $stats['summary'] ?? [];
	$content = $stats['content_management'] ?? [];
	$users = $stats['user_access'] ?? [];
	$traffic = $stats['traffic'] ?? [];
	$system = $stats['system_health'] ?? [];

	$formatNumber = function ($value) {
		if ($value === null) {
			return 'Not enabled';
		}
		return number_format((int) $value);
	};

	$formatBytes = function ($bytes) {
		if ($bytes === null) {
			return 'Not available';
		}

		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$value = (float) max(0, (int) $bytes);
		$power = $value > 0 ? (int) floor(log($value, 1024)) : 0;
		$power = min($power, count($units) - 1);
		$normalized = $value / (1024 ** $power);

		return number_format($normalized, $power === 0 ? 0 : 2) . ' ' . $units[$power];
	};

	$formatDateTime = function ($dateTime) {
		if (!$dateTime) {
			return 'Not available';
		}

		try {
			return (new DateTimeImmutable((string) $dateTime))->format('Y-m-d H:i');
		} catch (Throwable $e) {
			return (string) $dateTime;
		}
	};

	$summaryCards = [
		['label' => 'Total Content / Pages', 'value' => $formatNumber($summary['total_content'] ?? 0), 'icon' => 'file-text'],
		['label' => 'Published Posts', 'value' => $formatNumber($summary['published_posts'] ?? 0), 'icon' => 'check-circle'],
		['label' => 'Drafts / Pending Content', 'value' => $formatNumber($summary['draft_pending'] ?? 0), 'icon' => 'clock-3'],
		['label' => 'Total Views', 'value' => $formatNumber($summary['total_views'] ?? null), 'icon' => 'eye'],
		['label' => 'Total Users', 'value' => $formatNumber($summary['total_users'] ?? 0), 'icon' => 'users'],
		['label' => 'Comments Count', 'value' => $formatNumber($summary['comments_count'] ?? null), 'icon' => 'message-circle'],
	];
@endphp

<div class="space-y-6">
	<div>
		<h1 class="text-2xl font-bold text-gray-900">Dashboard Stats</h1>
		<p class="text-sm text-gray-500">Instant overview of content, users, traffic, and system health.</p>
	</div>

	<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
		@foreach($summaryCards as $card)
			<div class="bg-white border rounded-lg p-4">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
						<p class="text-2xl font-semibold text-gray-900 mt-1">{{ $card['value'] }}</p>
					</div>
					<div class="text-gray-400">
						<i data-lucide="{{ $card['icon'] }}"></i>
					</div>
				</div>
			</div>
		@endforeach
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<section class="bg-white border rounded-lg p-4">
			<h2 class="text-lg font-semibold text-gray-900 mb-4">Content Management</h2>
			<ul class="space-y-3 text-sm">
				<li class="flex justify-between"><span class="text-gray-600">New content this week</span><strong>{{ $formatNumber($content['new_content_week'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Most viewed content</span><strong>{{ isset($content['most_viewed']->title) ? $content['most_viewed']->title . ' (' . number_format((int) ($content['most_viewed']->metric ?? 0)) . ')' : 'Not enabled' }}</strong></li>
				<li>
					<div class="flex justify-between"><span class="text-gray-600">Recently updated items</span><strong>{{ is_array($content['recently_updated'] ?? null) ? count($content['recently_updated']) : 0 }}</strong></div>
					@if(!empty($content['recently_updated']))
						<div class="mt-2 space-y-1 text-xs text-gray-500">
							@foreach($content['recently_updated'] as $item)
								<div class="flex justify-between">
									<span>{{ $item->title ?? ('#' . ($item->id ?? '')) }}</span>
									<span>{{ $formatDateTime($item->updated_at ?? null) }}</span>
								</div>
							@endforeach
						</div>
					@endif
				</li>
				<li class="flex justify-between"><span class="text-gray-600">Scheduled posts</span><strong>{{ $formatNumber($content['scheduled_posts'] ?? 0) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Expiring content (next 7 days)</span><strong>{{ $formatNumber($content['expiring_content'] ?? null) }}</strong></li>
			</ul>
		</section>

		<section class="bg-white border rounded-lg p-4">
			<h2 class="text-lg font-semibold text-gray-900 mb-4">User & Access</h2>
			<ul class="space-y-3 text-sm">
				<li class="flex justify-between"><span class="text-gray-600">New users this month</span><strong>{{ $formatNumber($users['new_users_month'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Active users</span><strong>{{ $formatNumber($users['active_users'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Admin vs Editor count</span><strong>{{ ($users['admin_count'] ?? null) !== null && ($users['editor_count'] ?? null) !== null ? number_format((int) $users['admin_count']) . ' / ' . number_format((int) $users['editor_count']) : 'Not enabled' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Last login activity</span><strong>{{ $formatDateTime($users['last_login_activity'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Failed login attempts</span><strong>{{ $formatNumber($users['failed_login_attempts'] ?? null) }}</strong></li>
			</ul>
		</section>

		<section class="bg-white border rounded-lg p-4">
			<h2 class="text-lg font-semibold text-gray-900 mb-4">Traffic / Analytics</h2>
			<ul class="space-y-3 text-sm">
				<li class="flex justify-between"><span class="text-gray-600">Today's visits</span><strong>{{ $formatNumber($traffic['today_visits'] ?? null) }}</strong></li>
				<li>
					<div class="flex justify-between"><span class="text-gray-600">Weekly visits graph</span><strong>{{ !empty($traffic['weekly_visits']) ? 'Available' : 'Not enabled' }}</strong></div>
					@if(!empty($traffic['weekly_visits']))
						<div class="mt-2 grid grid-cols-7 gap-1 items-end h-20">
							@php
								$maxVisits = 0;
								foreach ($traffic['weekly_visits'] as $bar) {
									$maxVisits = max($maxVisits, (int) ($bar['visits'] ?? 0));
								}
							@endphp
							@foreach($traffic['weekly_visits'] as $bar)
								@php
									$visits = (int) ($bar['visits'] ?? 0);
									$height = $maxVisits > 0 ? max(8, (int) round(($visits / $maxVisits) * 72)) : 8;
								@endphp
								<div class="flex flex-col items-center justify-end gap-1">
									<div class="w-full bg-blue-500 rounded" style="height: {{ $height }}px" title="{{ $bar['day'] }}: {{ $visits }}"></div>
									<span class="text-[10px] text-gray-500">{{ substr((string) ($bar['day'] ?? ''), 5) }}</span>
								</div>
							@endforeach
						</div>
					@endif
				</li>
				<li>
					<div class="flex justify-between"><span class="text-gray-600">Top pages</span><strong>{{ !empty($traffic['top_pages']) ? 'Available' : 'Not enabled' }}</strong></div>
					@if(!empty($traffic['top_pages']))
						<div class="mt-2 space-y-1 text-xs text-gray-500">
							@foreach($traffic['top_pages'] as $row)
								<div class="flex justify-between">
									<span>{{ $row->title ?? ($row->slug ?? 'Untitled') }}</span>
									<span>{{ number_format((int) ($row->metric ?? 0)) }}</span>
								</div>
							@endforeach
						</div>
					@endif
				</li>
				<li class="flex justify-between"><span class="text-gray-600">Traffic sources</span><strong>{{ !empty($traffic['traffic_sources']) ? count($traffic['traffic_sources']) : 'Not enabled' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Bounce rate</span><strong>{{ ($traffic['bounce_rate'] ?? null) !== null ? number_format((float) $traffic['bounce_rate'], 2) . '%' : 'Not enabled' }}</strong></li>
			</ul>
		</section>

		<section class="bg-white border rounded-lg p-4">
			<h2 class="text-lg font-semibold text-gray-900 mb-4">System Health</h2>
			<ul class="space-y-3 text-sm">
				<li class="flex justify-between"><span class="text-gray-600">PHP version</span><strong>{{ $system['php_version'] ?? 'Unknown' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Database size</span><strong>{{ $formatBytes($system['database_size'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Disk usage</span><strong>{{ $formatBytes($system['disk_usage'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Cache status</span><strong>{{ !empty($system['cache_status']['enabled']) ? (!empty($system['cache_status']['writable']) ? 'Enabled' : 'Read-only') . ' (' . number_format((int) ($system['cache_status']['files'] ?? 0)) . ' files)' : 'Disabled' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Queue/jobs status</span><strong>{{ !empty($system['queue_status']['enabled']) ? ((($system['queue_status']['pending'] ?? null) !== null) ? number_format((int) $system['queue_status']['pending']) . ' pending' : 'Enabled') : 'Not enabled' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Backup status</span><strong>{{ !empty($system['backup_status']['enabled']) ? number_format((int) ($system['backup_status']['count'] ?? 0)) . ' backups' : 'Not enabled' }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Last backup</span><strong>{{ $formatDateTime($system['backup_status']['latest'] ?? null) }}</strong></li>
				<li class="flex justify-between"><span class="text-gray-600">Cron status</span><strong>{{ !empty($system['cron_status']['configured']) ? 'Configured (' . $formatDateTime($system['cron_status']['last_run'] ?? null) . ')' : 'Not configured' }}</strong></li>
			</ul>
		</section>
	</div>
</div>
@endsection
