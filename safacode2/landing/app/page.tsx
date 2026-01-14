"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Github, ChevronDown, Check, Copy, ArrowRight } from "lucide-react"

export default function SafeCodeLanding() {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [copied, setCopied] = useState(false)

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  }

  const copyToClipboard = () => {
    navigator.clipboard.writeText("irm https://safenode.cloud/safecode/install.ps1 | iex")
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="min-h-screen bg-background text-foreground">
      <nav className="fixed top-0 w-full z-50 border-b border-border/30 bg-background/80 backdrop-blur-xl">
        <div className="max-w-6xl mx-auto px-6">
          <div className="flex items-center justify-between h-14">
            <div className="flex items-center gap-2">
              <img 
                src="/logos (6).png" 
                alt="SafeCode Logo" 
                className="w-5 h-5 object-contain opacity-90"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                }}
              />
              <span className="font-medium text-sm tracking-tight">SafeCode</span>
              <span className="text-xs text-muted-foreground/50 ml-1">by SafeNode</span>
            </div>
            <div className="hidden md:flex items-center gap-6">
              <a href="#features" className="text-sm text-muted-foreground hover:text-foreground transition-colors">
                Features
              </a>
              <a href="#faq" className="text-sm text-muted-foreground hover:text-foreground transition-colors">
                FAQ
              </a>
              <Button variant="ghost" size="sm" className="gap-2 h-8 px-3 text-sm">
                <Github className="w-4 h-4" />
                GitHub
              </Button>
            </div>
          </div>
        </div>
      </nav>

      <section className="pt-32 pb-24 px-6">
        <div className="max-w-6xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h1 className="text-6xl md:text-7xl font-semibold mb-6 tracking-tight text-balance leading-none">
                Secure code,
                <br />
                <span className="text-muted-foreground">ship faster</span>
              </h1>

              <p className="text-base text-muted-foreground/80 mb-10 leading-relaxed max-w-xl text-balance">
                Professional security for developers. Real-time vulnerability detection integrated into your workflow.
              </p>

              <div className="mb-12">
                <div className="text-xs font-medium text-muted-foreground/60 mb-3 uppercase tracking-wider">
                  Get Started
                </div>
                <div className="rounded-lg border border-border/30 bg-card/50 p-4 font-mono text-sm flex items-center justify-between gap-4 mb-4">
                  <code className="text-muted-foreground/80 flex-1 text-left text-xs md:text-sm">
                    irm https://safenode.cloud/safecode/install.ps1 | iex
                  </code>
                  <Button size="sm" variant="ghost" onClick={copyToClipboard} className="h-8 px-3 gap-2 flex-shrink-0">
                    {copied ? (
                      <>
                        <Check className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Copied</span>
                      </>
                    ) : (
                      <>
                        <Copy className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Copy</span>
                      </>
                    )}
                  </Button>
                </div>
                <div className="flex items-center gap-3">
                  <Button size="lg" className="h-11 px-6 text-sm font-medium">
                    View Documentation
                    <ArrowRight className="w-4 h-4 ml-2" />
                  </Button>
                  <Button
                    variant="outline"
                    size="lg"
                    className="h-11 px-6 text-sm font-medium border-border/30 bg-transparent"
                  >
                    <Github className="w-4 h-4 mr-2" />
                    GitHub
                  </Button>
                </div>
              </div>
            </div>

            <div className="relative">
              <div className="rounded-lg border border-border/30 bg-card/50 overflow-hidden backdrop-blur-sm">
                <div className="flex items-center justify-between px-4 py-2.5 border-b border-border/30 bg-background/50">
                  <span className="text-xs text-muted-foreground font-mono">security-check.ts</span>
                  <div className="flex items-center gap-1.5 text-xs text-green-500">
                    <Check className="w-3 h-3" />
                    <span>No issues</span>
                  </div>
                </div>
                <div className="p-6 font-mono text-sm leading-relaxed">
                  <pre className="text-muted-foreground/80">
                    <span className="text-purple-400">import</span>
                    <span className="text-foreground/90">{" { SecurityScanner } "}</span>
                    <span className="text-purple-400">from</span>
                    <span className="text-green-400">{" '@safecode/core'"}</span>
                    {"\n\n"}
                    <span className="text-purple-400">const</span>
                    <span className="text-blue-400">{" scanner "}</span>
                    <span className="text-foreground/70">= </span>
                    <span className="text-purple-400">new</span>
                    <span className="text-blue-400">{" SecurityScanner"}</span>
                    <span className="text-foreground/70">({"{\n"}</span>
                    <span className="text-foreground/60">{"  realtime: "}</span>
                    <span className="text-orange-400">true</span>
                    <span className="text-foreground/70">{",\n"}</span>
                    <span className="text-foreground/60">{"  autofix: "}</span>
                    <span className="text-orange-400">true</span>
                    {",\n"}
                    <span className="text-foreground/60">{"  threats: ["}</span>
                    <span className="text-green-400">'xss'</span>
                    <span className="text-foreground/70">, </span>
                    <span className="text-green-400">'sql'</span>
                    <span className="text-foreground/70">, </span>
                    <span className="text-green-400">'csrf'</span>
                    <span className="text-foreground/60">{"]"}</span>
                    {"\n"}
                    <span className="text-foreground/70">{"})"}</span>
                    {"\n\n"}
                    <span className="text-blue-400">scanner</span>
                    <span className="text-foreground/70">.</span>
                    <span className="text-yellow-400">watch</span>
                    <span className="text-foreground/70">(</span>
                    <span className="text-green-400">'./src'</span>
                    <span className="text-foreground/70">)</span>
                  </pre>
                </div>
              </div>
              <div className="absolute -bottom-8 -right-8 w-64 h-64 bg-foreground/5 rounded-full blur-3xl -z-10" />
            </div>
          </div>
        </div>
      </section>

      <section className="py-20 px-6">
        <div className="max-w-5xl mx-auto">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-12">
            {[
              { value: "Real-time", label: "Detection" },
              { value: "500+", label: "Security Rules" },
              { value: "Zero", label: "Config" },
              { value: "Open", label: "Source" },
            ].map((stat, index) => (
              <div key={index}>
                <div className="text-3xl font-semibold mb-1 tracking-tight">{stat.value}</div>
                <div className="text-sm text-muted-foreground/70">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="features" className="py-24 px-6">
        <div className="max-w-5xl mx-auto">
          <div className="max-w-xl mb-16">
            <h2 className="text-4xl font-semibold mb-4 tracking-tight">Security-first</h2>
            <p className="text-base text-muted-foreground/70 leading-relaxed">
              Built with security at the core. Every feature designed to help you write safer code.
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-12">
            {[
              {
                title: "Real-time Scanning",
                desc: "Continuous security analysis as you type, catching vulnerabilities before production.",
              },
              {
                title: "Dependency Auditing",
                desc: "Automatic scanning of all dependencies for known vulnerabilities and outdated packages.",
              },
              {
                title: "Code Analysis",
                desc: "Deep static analysis to identify security flaws and potential attack vectors.",
              },
              {
                title: "Secret Detection",
                desc: "Automatically prevent accidental commits of API keys, tokens, and credentials.",
              },
            ].map((feature, index) => (
              <div key={index}>
                <h3 className="text-base font-medium mb-2 tracking-tight">{feature.title}</h3>
                <p className="text-sm text-muted-foreground/70 leading-relaxed">{feature.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="faq" className="py-24 px-6">
        <div className="max-w-2xl mx-auto">
          <div className="mb-12">
            <h2 className="text-4xl font-semibold mb-4 tracking-tight">FAQ</h2>
            <p className="text-base text-muted-foreground/70">Everything you need to know</p>
          </div>
          <div className="space-y-2">
            {[
              {
                q: "What makes SafeCode different?",
                a: "SafeCode is built specifically for security-conscious developers. We've integrated security scanning and vulnerability detection directly into the core experience.",
              },
              {
                q: "Does it work with existing projects?",
                a: "Yes. SafeCode is compatible with all major programming languages and frameworks. Simply open your project and start coding.",
              },
              {
                q: "Is SafeCode free?",
                a: "SafeCode is open source and free for individual developers. We offer enterprise plans with team management and priority support.",
              },
            ].map((faq, index) => (
              <div key={index} className="border-b border-border/20 last:border-0">
                <button
                  onClick={() => toggleFaq(index)}
                  className="w-full flex items-center justify-between py-5 text-left"
                >
                  <h3 className="text-sm font-medium pr-4">{faq.q}</h3>
                  <ChevronDown
                    className={`w-4 h-4 text-muted-foreground/50 transition-transform flex-shrink-0 ${
                      openFaqIndex === index ? "rotate-180" : ""
                    }`}
                  />
                </button>
                {openFaqIndex === index && (
                  <div className="pb-5">
                    <p className="text-sm text-muted-foreground/70 leading-relaxed">{faq.a}</p>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      <footer className="border-t border-border/20 py-16 px-6">
        <div className="max-w-6xl mx-auto">
          <div className="grid md:grid-cols-4 gap-12 mb-12">
            <div className="md:col-span-2">
              <div className="flex items-center gap-2 mb-4">
                <img 
                  src="/logos (6).png" 
                  alt="SafeCode Logo" 
                  className="w-5 h-5 object-contain opacity-90"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                  }}
                />
                <span className="font-medium text-sm tracking-tight">SafeCode</span>
              </div>
              <p className="text-sm text-muted-foreground/60 leading-relaxed max-w-sm">
                Professional development IDE for modern developers. Built by SafeNode.
              </p>
            </div>
            <div>
              <div className="text-xs font-medium text-muted-foreground/60 mb-4 uppercase tracking-wider">Product</div>
              <div className="space-y-3">
                <a
                  href="#features"
                  className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors"
                >
                  Features
                </a>
                <a href="#" className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">
                  Documentation
                </a>
                <a href="#" className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">
                  Pricing
                </a>
              </div>
            </div>
            <div>
              <div className="text-xs font-medium text-muted-foreground/60 mb-4 uppercase tracking-wider">
                Resources
              </div>
              <div className="space-y-3">
                <a href="#" className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">
                  GitHub
                </a>
                <a href="#" className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">
                  Support
                </a>
                <a href="#" className="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">
                  Community
                </a>
              </div>
            </div>
          </div>
          <div className="pt-8 border-t border-border/10">
            <div className="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-muted-foreground/50">
              <div>© 2026 SafeNode. All rights reserved.</div>
              <div className="flex items-center gap-6">
                <a href="#" className="hover:text-foreground transition-colors">
                  Privacy
                </a>
                <a href="#" className="hover:text-foreground transition-colors">
                  Terms
                </a>
                <a href="#" className="hover:text-foreground transition-colors">
                  Security
                </a>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}
