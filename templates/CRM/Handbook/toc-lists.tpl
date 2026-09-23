{* One source's landing page and its pages by section; used by the sidebar and the index cards. *}
<ul class="handbook-toc-list">
  {if $group.landing}
    <li><a href="{$group.landing.url|escape}"{if $group.landing.current} aria-current="page" class="handbook-current"{/if}>{ts domain="handbook"}Übersicht{/ts}</a></li>
  {/if}
</ul>
{foreach from=$group.sections item=section}
  {if $section.name}<h3 class="handbook-section-name">{$section.name|escape}</h3>{/if}
  <ul class="handbook-toc-list">
    {foreach from=$section.pages item=entry}
      <li><a href="{$entry.url|escape}"{if $entry.current} aria-current="page" class="handbook-current"{/if}>{$entry.title|escape}</a></li>
    {/foreach}
  </ul>
{/foreach}
