<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  {if $handbookPage}
    <div class="handbook-layout">
      {include file="CRM/Handbook/toc.tpl"}
      <article class="handbook-page">
        {if $handbookSections}
          <nav class="handbook-sections" aria-label="{ts domain="handbook"}Abschnitte dieser Seite{/ts}">
            <ul>
              {foreach from=$handbookSections item=section}
                <li class="handbook-level-{$section.level}"><a href="#{$section.slug|escape}">{$section.text|escape}</a></li>
              {/foreach}
            </ul>
          </nav>
        {/if}
        <div class="handbook-content">{$handbookHtml|smarty:nodefaults}</div>
      </article>
    </div>
  {else}
    {if $handbookErrors}
      <div class="handbook-errors alert alert-danger crm-error" role="alert">
        <p><strong>{ts domain="handbook"}Fehler im Handbuch:{/ts}</strong></p>
        <ul>
          {foreach from=$handbookErrors item=error}<li>{$error|escape}</li>{/foreach}
        </ul>
      </div>
    {/if}
    {include file="CRM/Handbook/toc.tpl"}
  {/if}
</div>
