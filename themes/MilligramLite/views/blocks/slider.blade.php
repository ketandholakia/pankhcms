@php
use App\Models\SliderImage;
$sliderImages = SliderImage::where('active', 1)->orderBy('sort_order')->get();
@endphp

@if($sliderImages->count())
<style>
  .pankh-slider{position:relative;overflow:hidden;width:100%;background:#111;margin-bottom:2rem}
  .pankh-slider-track{display:flex;transition:transform .6s ease}
  .pankh-slider-track>div{min-width:100%;position:relative}
  .pankh-slider-track img{width:100%;max-height:420px;object-fit:cover;display:block}
  .pankh-slider-caption{position:absolute;bottom:0;left:0;right:0;padding:1rem;background:rgba(0,0,0,.5);color:#fff;text-align:center}
  .pankh-slider-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.4);color:#fff;border:none;cursor:pointer;padding:.5rem .8rem;font-size:1.2rem;border-radius:50%}
  .pankh-slider-btn:hover{background:rgba(0,0,0,.7)}
  .pankh-slider-btn.prev{left:.5rem}
  .pankh-slider-btn.next{right:.5rem}
  .pankh-slider-dots{position:absolute;bottom:.5rem;left:50%;transform:translateX(-50%);display:flex;gap:.4rem}
  .pankh-slider-dots button{width:10px;height:10px;border-radius:50%;border:none;cursor:pointer;background:rgba(255,255,255,.5)}
  .pankh-slider-dots button.active{background:#fff}
</style>

<div class="pankh-slider" id="pankh-slider">
  <div class="pankh-slider-track" id="pankh-slider-track">
    @foreach($sliderImages as $slide)
    <div>
      <img src="{{ $slide->image_path }}" alt="{{ $slide->caption ?? '' }}">
      @if($slide->caption || $slide->link)
      <div class="pankh-slider-caption">
        @if($slide->caption)<p style="margin:0 0 .5rem">{{ $slide->caption }}</p>@endif
        @if($slide->link)<a href="{{ $slide->link }}" style="color:#fff;text-decoration:underline">Learn More</a>@endif
      </div>
      @endif
    </div>
    @endforeach
  </div>

  @if($sliderImages->count() > 1)
  <button class="pankh-slider-btn prev" onclick="pankhSliderMove(-1)">&#8592;</button>
  <button class="pankh-slider-btn next" onclick="pankhSliderMove(1)">&#8594;</button>
  <div class="pankh-slider-dots" id="pankh-slider-dots">
    @foreach($sliderImages as $i => $slide)
    <button class="{{ $i === 0 ? 'active' : '' }}" onclick="pankhSliderGoTo({{ $i }})"></button>
    @endforeach
  </div>
  @endif
</div>

<script>
(function(){
  var track=document.getElementById('pankh-slider-track');
  var dots=document.querySelectorAll('#pankh-slider-dots button');
  var cur=0,total={{ $sliderImages->count() }},timer;
  function goTo(n){cur=(n+total)%total;track.style.transform='translateX(-'+(cur*100)+'%)';dots.forEach(function(d,i){d.classList.toggle('active',i===cur);});}
  window.pankhSliderMove=function(d){clearInterval(timer);goTo(cur+d);start();}
  window.pankhSliderGoTo=function(n){clearInterval(timer);goTo(n);start();}
  function start(){timer=setInterval(function(){goTo(cur+1);},5000);}
  start();
})();
</script>
@endif
