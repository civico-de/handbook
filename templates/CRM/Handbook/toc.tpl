<nav class="handbook-toc" aria-label="{ts domain="handbook"}Inhaltsverzeichnis{/ts}">
  {foreach from=$handbookToc item=group}
    <h2>{if $group.url}<a href="{$group.url|escape}"{if $group.current} aria-current="page" class="handbook-current"{/if}>{$group.label|escape}</a>{else}{$group.label|escape}{/if}</h2>
    <ul>
      {foreach from=$group.pages item=entry}
        <li><a href="{$entry.url|escape}"{if $entry.current} aria-current="page" class="handbook-current"{/if}>{$entry.title|escape}</a></li>
      {/foreach}
    </ul>
  {foreachelse}
    <p>{ts domain="handbook"}Es sind noch keine Anleitungen vorhanden.{/ts}</p>
  {/foreach}
</nav>
