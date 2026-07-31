{{- define "slim4-app.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "slim4-app.fullname" -}}
{{- if .Values.fullnameOverride -}}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- $name := default .Chart.Name .Values.nameOverride -}}
{{- if contains $name .Release.Name -}}
{{- .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}
{{- end -}}

{{- define "slim4-app.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "slim4-app.labels" -}}
helm.sh/chart: {{ include "slim4-app.chart" . }}
{{ include "slim4-app.selectorLabels" . }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}

{{- define "slim4-app.selectorLabels" -}}
app.kubernetes.io/name: {{ include "slim4-app.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end -}}

{{- define "slim4-app.serviceAccountName" -}}
{{- if .Values.serviceAccount.create -}}
{{- default (include "slim4-app.fullname" .) .Values.serviceAccount.name -}}
{{- else -}}
{{- default "default" .Values.serviceAccount.name -}}
{{- end -}}
{{- end -}}

{{- define "slim4-app.consumerFullname" -}}
{{- printf "%s-consumer-%s" (include "slim4-app.fullname" .root) .name | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "slim4-app.consumerSelectorLabels" -}}
{{ include "slim4-app.selectorLabels" .root }}
app.kubernetes.io/component: consumer
messaging.slim4-app/consumer: {{ .name }}
{{- end -}}

{{- define "slim4-app.consumerLabels" -}}
helm.sh/chart: {{ include "slim4-app.chart" .root }}
{{ include "slim4-app.consumerSelectorLabels" . }}
messaging.slim4-app/broker: {{ .consumer.broker | required (printf "consumers.%s.broker is required" .name) }}
app.kubernetes.io/managed-by: {{ .root.Release.Service }}
{{- end -}}

{{- define "slim4-app.consumerSource" -}}
{{- .consumer.source | required (printf "consumers.%s.source is required" .name) -}}
{{- end -}}

{{- define "slim4-app.secretName" -}}
{{- if .Values.env.existingSecret -}}
{{- .Values.env.existingSecret -}}
{{- else -}}
{{- include "slim4-app.fullname" . -}}
{{- end -}}
{{- end -}}
