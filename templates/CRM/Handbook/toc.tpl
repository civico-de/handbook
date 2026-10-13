<nav class="handbook-toc" aria-label="{ts domain="handbook"}Contents{/ts}">
  {foreach from=$handbookToc item=group}
    <details class="handbook-group"{if $group.open} open{/if}>
      <summary><h2 class="handbook-group-label">{$group.label|escape}</h2></summary>
      {include file="CRM/Handbook/toc-lists.tpl" group=$group}
    </details>
  {foreachelse}
    <p>{ts domain="handbook"}There are no manuals yet.{/ts}</p>
  {/foreach}
</nav>
