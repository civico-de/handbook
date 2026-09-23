<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  {if $handbookPage}
    <div class="handbook-layout">
      {include file="CRM/Handbook/toc.tpl"}
      <div class="handbook-main">
        <nav class="handbook-breadcrumb" aria-label="{ts domain="handbook"}Pfad im Handbuch{/ts}">
          <ol>
            {foreach from=$handbookBreadcrumb item=crumb}
              <li>{if $crumb.url}<a href="{$crumb.url|escape}">{$crumb.label|escape}</a>{else}{$crumb.label|escape}{/if}</li>
            {/foreach}
          </ol>
        </nav>
        <div class="handbook-article">
          {if $handbookSections}
            <nav class="handbook-sections" aria-labelledby="handbook-sections-heading">
              <h2 id="handbook-sections-heading">{ts domain="handbook"}Auf dieser Seite{/ts}</h2>
              <ul>
                {foreach from=$handbookSections item=section}
                  <li class="handbook-level-{$section.level}"><a href="#{$section.slug|escape}">{$section.text|escape}</a></li>
                {/foreach}
              </ul>
            </nav>
          {/if}
          <article class="handbook-content">{$handbookHtml|smarty:nodefaults}</article>
        </div>
        {if $handbookPager.prev || $handbookPager.next}
          <nav class="handbook-pager" aria-label="{ts domain="handbook"}Vorherige und nächste Seite{/ts}">
            {if $handbookPager.prev}
              <a class="handbook-pager-prev" rel="prev" href="{$handbookPager.prev.url|escape}"><span class="handbook-pager-dir">{ts domain="handbook"}Vorherige Seite{/ts}</span> {$handbookPager.prev.title|escape}</a>
            {/if}
            {if $handbookPager.next}
              <a class="handbook-pager-next" rel="next" href="{$handbookPager.next.url|escape}"><span class="handbook-pager-dir">{ts domain="handbook"}Nächste Seite{/ts}</span> {$handbookPager.next.title|escape}</a>
            {/if}
          </nav>
        {/if}
      </div>
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
    <nav class="handbook-cards" aria-label="{ts domain="handbook"}Inhaltsverzeichnis{/ts}">
      {foreach from=$handbookToc item=group}
        <section class="handbook-card" aria-labelledby="handbook-card-{$group.source|escape}">
          <h2 id="handbook-card-{$group.source|escape}">{$group.label|escape}</h2>
          {include file="CRM/Handbook/toc-lists.tpl" group=$group}
        </section>
      {foreachelse}
        <p>{ts domain="handbook"}Es sind noch keine Anleitungen vorhanden.{/ts}</p>
      {/foreach}
    </nav>
  {/if}
</div>
