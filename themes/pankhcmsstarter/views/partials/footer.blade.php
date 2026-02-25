<footer class="footer">
    <div class="content has-text-centered">
        {!! blocks_html('footer') !!}
        <p>&copy; {{ date('Y') }} PankhCMS
            @php
                $showCredit = setting('show_theme_credit', '1') === '1';
                $theme = \App\Core\Theme::active();
                $themeJsonPath = \App\Core\Theme::path($theme, 'theme.json');
                $themeAuthor = null;
                $themeAuthorUrl = null;
                if (is_file($themeJsonPath)) {
                    $themeMeta = json_decode(file_get_contents($themeJsonPath), true);
                    if (is_array($themeMeta)) {
                        $themeAuthor = $themeMeta['author'] ?? null;
                        $themeAuthorUrl = $themeMeta['author_url'] ?? null;
                    }
                }
            @endphp
            @if($showCredit && $themeAuthor)
                <br>Theme created by
                @if($themeAuthorUrl)
                    <a href="{{ $themeAuthorUrl }}" target="_blank" rel="noopener noreferrer">{{ $themeAuthor }}</a>
                @else
                    {{ $themeAuthor }}
                @endif
            @endif
        </p>
    </div>
</footer>
