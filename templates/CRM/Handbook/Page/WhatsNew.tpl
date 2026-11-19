<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  <div class="handbook-layout">
    {include file="CRM/Handbook/toc.tpl"}
    <div class="handbook-main handbook-whats-new">
      {foreach from=$handbookReleases item=release}
        <section class="handbook-release" aria-labelledby="handbook-release-{$release.source|escape}">
          <h2 id="handbook-release-{$release.source|escape}">{$release.label|escape} {$release.version|escape}</h2>
          {if $release.date}<p class="handbook-release-date">{$release.date|crmDate|escape}</p>{/if}
          <div class="handbook-content">{$release.html|smarty:nodefaults}</div>
          <p><a href="{$release.url|escape}">{ts domain="handbook" 1=$release.label|escape}All changes in %1{/ts}</a></p>
        </section>
      {foreachelse}
        <p>{ts domain="handbook"}No extension in the handbook has released changes yet.{/ts}</p>
      {/foreach}
    </div>
  </div>
</div>
